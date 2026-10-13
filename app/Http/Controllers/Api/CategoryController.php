<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sortField = request('sort_field', 'updated_at');
        $sortDirection = request('sort_direction', 'desc');

        $categories = Category::query()
            ->orderBy($sortField, $sortDirection)
            ->latest()
            ->get();

        return CategoryResource::collection($categories);
    }

    public function getAsTree()
    {
        return Category::getActiveAsTree(CategoryTreeResource::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();
        unset($data['image']);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $category = Category::create($data);

        if ($request->hasFile('image')) {
            $category->saveImage($request->file('image'));
        }

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        unset($data['image'], $data['remove_image']);
        $data['updated_by'] = $request->user()->id;
        $category->update($data);

        if ($request->hasFile('image')) {
            $category->saveImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $category->deleteImageFile();
            $category->update(['image' => null]);
        }

        return new CategoryResource($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->deleteImageFile();
        $category->delete();

        return response()->noContent();
    }
}
