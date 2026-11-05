<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An uploaded file kept in the database instead of the disk.
 *
 * Render's free web service has an ephemeral filesystem, so files written under
 * storage/app/public disappear on every deploy or restart. Keeping the bytes in
 * MySQL lets image URLs survive on free infrastructure.
 */
class MediaFile extends Model
{
    use HasFactory;

    protected $primaryKey = 'path';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'path',
        'mime',
        'size',
        'contents',
    ];
}
