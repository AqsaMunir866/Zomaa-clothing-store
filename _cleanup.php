<?php

require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$product = App\Models\Product::withTrashed()->find(27);
if ($product) {
    foreach ($product->images as $image) {
        Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory(dirname($image->path));
        $image->delete();
    }
    App\Models\ProductCategory::where('product_id', 27)->delete();
    $product->forceDelete();
}

echo 'products=' . App\Models\Product::withTrashed()->count()
    . ' images=' . App\Models\ProductImage::count() . PHP_EOL;
