<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = Banner::query()
            ->orderBy('position')
            ->orderBy('sort_order')
            ->latest()
            ->get();

        return BannerResource::collection($banners);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BannerRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['active'] = (bool) ($data['active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['image_url'] = null;

        if ($request->hasFile('image')) {
            $this->saveImage($data, $request->file('image'));
        }

        $banner = Banner::create($data);

        return new BannerResource($banner);
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return new BannerResource($banner);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BannerRequest $request, Banner $banner)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;
        $data['active'] = (bool) ($data['active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? $banner->sort_order);

        if ($request->hasFile('image')) {
            $this->deleteImage($banner);
            $data['image_url'] = null;
            $this->saveImage($data, $request->file('image'));
        }

        $banner->update($data);

        return new BannerResource($banner);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        $this->deleteImage($banner);
        $banner->delete();

        return response()->noContent();
    }

    private function saveImage(array &$data, $image)
    {
        $path = 'banners/' . Str::random(40);
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0755, true);
        }
        $name = Str::random().'.'.$image->getClientOriginalExtension();
        if (!Storage::putFileAs('public/' . $path, $image, $name)) {
            throw new \Exception("Unable to save file \"{$image->getClientOriginalName()}\"");
        }
        $relativePath = $path . '/' . $name;
        $data['image_path'] = $relativePath;
        $data['image_url'] = \Illuminate\Support\Facades\URL::to(Storage::url($relativePath));
    }

    private function deleteImage(Banner $banner)
    {
        if ($banner->image_path) {
            Storage::deleteDirectory('/public/' . dirname($banner->image_path));
        }
    }
}