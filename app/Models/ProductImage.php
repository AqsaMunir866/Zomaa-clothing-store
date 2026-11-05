<?php

namespace App\Models;

use App\Support\MediaStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'url',
        'mime',
        'size',
        'position',
    ];

    /**
     * Resolve the URL from the stored path so the same row works on the local
     * disk and in the database media store.
     *
     * The "url" column is kept as a fallback for rows written by older
     * versions of the app (and for seeded artwork shipped with the repo).
     */
    public function getUrlAttribute($value): ?string
    {
        if (!$this->path) {
            return $value;
        }

        return app(MediaStorage::class)->storedUrl($this->path, $value);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
