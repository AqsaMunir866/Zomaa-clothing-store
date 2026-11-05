<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'active', 'parent_id', 'image', 'created_by', 'updated_by'];

    public function saveImage($image)
    {
        $this->deleteImageFile();

        $media = app(MediaStorage::class)->put($image, 'categories');
        $this->update(['image' => $media['path']]);

        return $media['path'];
    }

    public function deleteImageFile()
    {
        if ($this->image) {
            app(MediaStorage::class)->delete($this->image);
        }
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image
            ? app(MediaStorage::class)->storedUrl($this->image, asset('storage/' . $this->image))
            : null;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class); // product_category
    }

    public static function getActiveAsTree($resourceClassName = null)
    {
        $categories = Category::where('active', true)->orderBy('parent_id')->get();
        return self::buildCategoryTree($categories, null, $resourceClassName);
    }

    public static function getAllChildrenByParent(Category $category)
    {
        $categories = Category::where('active', true)->orderBy('parent_id')->get();
        $result[] = $category;
        self::getCategoriesArray($categories, $category->id, $result);

        return $result;
    }

    private static function buildCategoryTree($categories, $parentId = null, $resourceClassName = null)
    {
        $categoryTree = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = self::buildCategoryTree($categories, $category->id, $resourceClassName);
                if ($children) {
                    $category->setAttribute('children', $children);
                }
                $categoryTree[] = $resourceClassName ? new $resourceClassName($category) : $category;
            }
        }

        return $categoryTree;
    }

    private static function getCategoriesArray($categories, $parentId, &$result)
    {
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $result[] = $category;
                self::getCategoriesArray($categories, $category->id, $result);
            }
        }
    }
}
