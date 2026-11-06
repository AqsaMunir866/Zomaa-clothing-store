<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media driver
    |--------------------------------------------------------------------------
    |
    | Where user uploads (product photos, category art, banners, payment
    | proofs) are kept.
    |
    | "local"    - storage/app/public, served from /storage. Fast for
    |              development. Files are lost when a container is replaced.
    | "database" - MySQL (media_files), served from /media/<path>. Survives
    |              redeploys on hosts with an ephemeral disk, such as Render's
    |              free tier.
    | "s3"       - any S3 compatible bucket (AWS S3, Cloudflare R2, ...) using
    |              the "s3" disk in config/filesystems.php.
    |
    | Production defaults to "database" so a free deploy works without extra
    | configuration; set MEDIA_DRIVER explicitly to override.
    |
    */

    // `?:` (not a default argument) matters: an empty MEDIA_DRIVER= in the
    // hosting dashboard still counts as "set". With env('MEDIA_DRIVER', ...)
    // it would resolve to "" and silently fall back to the local disk, so
    // uploads vanished on the next deploy.
    'driver' => env('MEDIA_DRIVER') ?: (env('APP_ENV') === 'production' ? 'database' : 'local'),

];
