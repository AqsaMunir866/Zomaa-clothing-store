<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;

class MediaController extends Controller
{
    /**
     * Serve an upload kept in the database (MEDIA_DRIVER=database).
     *
     * Paths are random and never reused, so the response can be cached forever.
     */
    public function show(string $path)
    {
        $file = MediaFile::find($path);

        if (!$file) {
            abort(404);
        }

        $etag = '"' . md5($file->path . ':' . $file->size) . '"';

        if (request()->header('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return response($file->contents, 200, [
            'Content-Type' => $file->mime,
            'Content-Length' => strlen($file->contents),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }
}
