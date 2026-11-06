<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'kicker',
        'text',
        'image_path',
        'image_url',
        'link_label',
        'link_url',
        'position',
        'sort_order',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Resolve the image from image_path when possible, exactly like product and
     * category images. The image_url column is only a fallback for seeded art
     * and for rows written before uploads moved to the media store.
     */
    public function getImageUrlAttribute($value): ?string
    {
        if (!$this->image_path) {
            return $value;
        }

        return app(MediaStorage::class)->storedUrl($this->image_path, $value);
    }

    public function image($fallback = null): ?string
    {
        return $this->image_url ?: $fallback;
    }
}