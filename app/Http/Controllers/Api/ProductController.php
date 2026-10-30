<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Api\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $perPage = request('per_page', 10);
        $search = request('search', '');
        $sortField = request('sort_field', 'created_at');
        $sortDirection = request('sort_direction', 'desc');

        $query = Product::query()
            ->where('title', 'like', "%{$search}%")
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);

        return ProductListResource::collection($query);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $images = $data['images'] ?? [];
        $imageOrder = $data['image_order'] ?? [];
        $categories = $data['categories'] ?? [];
        unset($data['images'], $data['image_order'], $data['deleted_images']);

        $product = DB::transaction(function () use ($data, $images, $imageOrder, $categories) {
            $product = Product::create($data);

            $this->saveCategories($categories, $product);
            $this->syncImages($product, $images, $imageOrder, []);

            return $product;
        });

        return new ProductResource($product->load('images'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product      $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $images = $data['images'] ?? [];
        $deletedImages = $data['deleted_images'] ?? [];
        $imageOrder = $data['image_order'] ?? [];
        $categories = $data['categories'] ?? [];
        unset($data['images'], $data['image_order'], $data['deleted_images']);

        DB::transaction(function () use ($product, $data, $images, $imageOrder, $deletedImages, $categories) {
            $this->saveCategories($categories, $product);
            $this->syncImages($product, $images, $imageOrder, $deletedImages);

            $product->update($data);
        });

        return new ProductResource($product->load('images'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->noContent();
    }

    private function saveCategories($categoryIds, Product $product)
    {
        ProductCategory::where('product_id', $product->id)->delete();
        $data = array_map(fn($id) => (['category_id' => $id, 'product_id' => $product->id]), $categoryIds);

        ProductCategory::insert($data);
    }

    /**
     * Reconcile the product's images with what the admin form submitted.
     *
     * @param \Illuminate\Http\UploadedFile[] $images new uploads, in display order
     * @param string[]                        $order  display order: "existing:<id>" / "new:<uploadIndex>"
     * @param int[]                           $deletedIds images the admin removed
     */
    private function syncImages(Product $product, array $images, array $order, array $deletedIds)
    {
        $existing = $product->images()->get()->keyBy('id');

        // Older clients send no order: keep existing images, append the uploads.
        if (empty($order)) {
            $order = $existing->keys()
                ->reject(fn($id) => in_array($id, array_map('intval', $deletedIds), true))
                ->map(fn($id) => "existing:{$id}")
                ->values()
                ->all();

            foreach (array_keys($images) as $index) {
                $order[] = "new:{$index}";
            }
        }

        $keptIds = [];

        foreach ($order as $position => $token) {
            $separator = strpos($token, ':');
            if ($separator === false) {
                continue;
            }

            [$type, $value] = [substr($token, 0, $separator), substr($token, $separator + 1)];

            if ($type === 'existing') {
                $id = (int)$value;
                if (in_array($id, array_map('intval', $deletedIds), true)) {
                    continue;
                }
                $image = $existing->get($id);
                if (!$image) {
                    continue;
                }
                $keptIds[] = $id;
                $this->moveImageToPosition($image, $position + 1);
            } elseif ($type === 'new' && isset($images[(int)$value])) {
                $file = $images[(int)$value];
                if ($file instanceof UploadedFile) {
                    $this->storeImage($product, $file, $position + 1);
                }
                unset($images[(int)$value]);
            }
        }

        // Anything the form no longer shows is removed, row and file.
        foreach ($existing as $id => $image) {
            if (!in_array($id, $keptIds, true)) {
                $this->deleteImage($image);
            }
        }

        // Safety net: uploads the client did not place explicitly.
        $position = (int)$product->images()->max('position');
        foreach ($images as $file) {
            if ($file instanceof UploadedFile) {
                $position++;
                $this->storeImage($product, $file, $position);
            }
        }

        $this->renumberImages($product);
    }

    private function moveImageToPosition(ProductImage $image, int $position)
    {
        if ((int)$image->position !== $position) {
            $image->update(['position' => $position]);
        }
    }

    /**
     * Keep positions as 1..n so ordering never depends on ties or gaps.
     */
    private function renumberImages(Product $product)
    {
        $images = $product->images()->get();
        $position = 0;

        foreach ($images as $image) {
            $position++;
            if ((int)$image->position !== $position) {
                $image->update(['position' => $position]);
            }
        }
    }

    private function storeImage(Product $product, UploadedFile $file, int $position)
    {
        $directory = 'images/' . Str::random();
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $name = Str::random() . '.' . strtolower($extension);

        if (!Storage::disk('public')->putFileAs($directory, $file, $name)) {
            throw new \Exception("Unable to save file \"{$file->getClientOriginalName()}\"");
        }

        $path = $directory . '/' . $name;

        ProductImage::create([
            'product_id' => $product->id,
            'path' => $path,
            'url' => URL::to(Storage::disk('public')->url($path)),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'position' => $position,
        ]);
    }

    private function deleteImage(ProductImage $image)
    {
        if ($image->path) {
            $directory = dirname($image->path);
            if ($directory !== '.' && $directory !== '') {
                Storage::disk('public')->deleteDirectory($directory);
            }
        }

        $image->delete();
    }
}
