<?php

namespace App\Support;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Single entry point for user uploaded media.
 *
 * Uploads are identified by a path, never by a full URL, so the same record can
 * be served from disk in development and from MySQL (or S3) in production:
 *
 *   images/k8s2n1p9q0x7/yT4m...jpg    <- upload, one random folder per file
 *   categories/t-shirts.jpg           <- seeded artwork, shipped with the repo
 *
 * Only paths inside a private upload folder are removed; seeded category
 * artwork is shared and is never deleted.
 */
class MediaStorage
{
    public const LOCAL = 'local';

    public const DATABASE = 'database';

    public const S3 = 's3';

    /** @var array<string, bool> */
    private static array $stored = [];

    public function driver(): string
    {
        return (string) config('media.driver', self::LOCAL);
    }

    /**
     * Persist an upload and return the values a model needs to store.
     *
     * @return array{path: string, url: string, mime: string, size: int}
     */
    public function put(UploadedFile $file, string $type = 'images'): array
    {
        $path = $this->newPath($file, $type);
        $mime = $file->getClientMimeType() ?: 'application/octet-stream';
        $size = (int) $file->getSize();

        switch ($this->driver()) {
            case self::DATABASE:
                MediaFile::create([
                    'path' => $path,
                    'mime' => $mime,
                    'size' => $size,
                    'contents' => file_get_contents($file->getRealPath()),
                ]);
                break;

            case self::S3:
                $stored = Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), [
                    'visibility' => 'public',
                    'ContentType' => $mime,
                ]);

                if (!$stored) {
                    throw new \RuntimeException("Unable to save file \"{$file->getClientOriginalName()}\"");
                }
                break;

            default:
                $stored = Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

                if (!$stored) {
                    throw new \RuntimeException("Unable to save file \"{$file->getClientOriginalName()}\"");
                }
                break;
        }

        self::$stored[$path] = true;

        return [
            'path' => $path,
            'url' => $this->url($path),
            'mime' => $mime,
            'size' => $size,
        ];
    }

    public function url(string $path): string
    {
        return match ($this->driver()) {
            self::DATABASE => url('/media/' . $path),
            self::S3 => Storage::disk('s3')->url($path),
            default => Storage::disk('public')->url($path),
        };
    }

    /**
     * URL for a path that may predate the current driver (for example a seeded
     * /storage path, or an upload made before MEDIA_DRIVER became "database").
     */
    public function storedUrl(string $path, ?string $fallback = null): ?string
    {
        if ($path === '') {
            return $fallback;
        }

        if ($this->driver() === self::LOCAL) {
            return $this->url($path);
        }

        if ($this->exists($path)) {
            return $this->url($path);
        }

        // A seeded file (categories/men.png) or an upload made before the
        // driver was switched still lives on the public disk.
        return $fallback ?? Storage::disk('public')->url($path);
    }

    public function exists(string $path): bool
    {
        if (!array_key_exists($path, self::$stored)) {
            self::$stored[$path] = match ($this->driver()) {
                self::DATABASE => MediaFile::whereKey($path)->exists(),
                self::S3 => Storage::disk('s3')->exists($path),
                default => Storage::disk('public')->exists($path),
            };
        }

        return self::$stored[$path];
    }

    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        unset(self::$stored[$path]);

        if ($this->driver() === self::DATABASE) {
            MediaFile::whereKey($path)->delete();
            // Also clear a copy left on disk before the driver was switched.
            $this->deleteFromDisk($path);

            return;
        }

        if ($this->driver() === self::S3) {
            Storage::disk('s3')->delete($path);

            return;
        }

        $this->deleteFromDisk($path);
    }

    private function deleteFromDisk(string $path): void
    {
        $segments = explode('/', trim($path, '/'));

        // Seeded artwork (categories/men.png) is shared by many products;
        // deleting it would break every other reference.
        if (count($segments) === 2 && $segments[0] === 'categories') {
            return;
        }

        $disk = Storage::disk('public');
        $disk->delete($path);

        // Uploads live in their own random folder (images/<random>/file), so
        // the folder can go too. Never remove a shared parent directory such
        // as payment-proofs/.
        if (count($segments) >= 3) {
            $disk->deleteDirectory(dirname($path));
        }
    }

    private function newPath(UploadedFile $file, string $type): string
    {
        $extension = Str::lower(
            preg_replace('/[^a-z0-9]/i', '', $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin')
        );

        return trim($type, '/') . '/' . Str::random(16) . '/' . Str::random(24) . '.' . $extension;
    }
}
