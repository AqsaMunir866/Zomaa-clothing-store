<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\BannerAndSettingsSeeder;
use Database\Seeders\ClothingStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard for the live-site bug where every deploy reset banner
 * images (and product details) back to the demo seed values.
 */
class SeedersAreNonDestructiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_does_not_overwrite_admin_edits(): void
    {
        $admin = User::factory()->admin()->create();

        $product = Product::create([
            'title' => 'Classic Cotton T-Shirt',
            'description' => 'Admin edited description',
            'price' => 9999,
            'quantity' => 3,
            'published' => false,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'images/custom/cover.jpg',
            'url' => 'https://example.test/media/images/custom/cover.jpg',
            'mime' => 'image/jpeg',
            'size' => 10,
            'position' => 1,
        ]);

        $this->seed(ClothingStoreSeeder::class);

        $product->refresh();

        $this->assertSame('Admin edited description', $product->description);
        $this->assertSame('9999.00', (string) $product->price);
        $this->assertSame(3, (int) $product->quantity);
        $this->assertSame(0, (int) $product->published);
        $this->assertSame(
            ['images/custom/cover.jpg'],
            $product->images()->pluck('path')->all()
        );
    }

    public function test_branding_seeder_does_not_overwrite_admin_edits(): void
    {
        $banner = Banner::create([
            'title' => 'Soft Cotton, Quietly Defined',
            'position' => 'hero',
            'image_path' => 'banners/custom/cover.jpg',
            'image_url' => 'https://example.test/media/banners/custom/cover.jpg',
            'active' => true,
            'sort_order' => 0,
        ]);

        Setting::create(['key' => 'business_name', 'value' => 'My Demo Shop']);

        $this->seed(BannerAndSettingsSeeder::class);

        $banner->refresh();

        $this->assertSame(
            'https://example.test/media/banners/custom/cover.jpg',
            $banner->getRawOriginal('image_url')
        );
        $this->assertSame('banners/custom/cover.jpg', $banner->image_path);
        $this->assertSame(
            'My Demo Shop',
            Setting::where('key', 'business_name')->value('value')
        );
    }
}
