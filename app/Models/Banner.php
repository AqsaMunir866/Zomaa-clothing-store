<?php

namespace App\Models;

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

    public function image($fallback = null): ?string
    {
        return $this->image_url ?: $fallback;
    }
}