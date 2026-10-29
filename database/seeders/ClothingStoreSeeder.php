<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClothingStoreSeeder extends Seeder
{
    public function run()
    {
        // Don't assume the admin is user #1 (it isn't if the DB already has users).
        $adminId = \App\Models\User::where('is_admin', true)->value('id') ?? 1;

        $categories = [
            'Men' => ['T-Shirts', 'Shirts', 'Jeans', 'Jackets'],
            'Women' => ['Dresses', 'Tops', 'Skirts', 'Leggings'],
        ];

        $categoryIds = [];
        foreach ($categories as $parentName => $children) {
            $parent = Category::firstOrCreate(
                ['name' => $parentName],
                ['active' => true, 'created_by' => $adminId, 'updated_by' => $adminId]
            );
            foreach ($children as $childName) {
                $child = Category::firstOrCreate(
                    ['name' => $childName],
                    ['active' => true, 'parent_id' => $parent->id, 'created_by' => $adminId, 'updated_by' => $adminId]
                );
                $categoryIds[$childName] = $child->id;
            }
        }

        $products = [
            ['Classic Cotton T-Shirt', 2499, 60, 'Men', 'T-Shirts', 'cotton-tee', 'Soft combed cotton tee with a timeless crew neckline. Breathable everyday essential that pairs with anything from denim to chinos.'],
            ['Graphic Print T-Shirt', 2999, 45, 'Men', 'T-Shirts', 'graphic-tee', 'Statement graphic tee with a bold front print. Pre-shrunk cotton keeps its shape wash after wash.'],
            ['Slim Fit Polo Shirt', 3999, 40, 'Men', 'T-Shirts', 'polo-shirt', 'Slim-fit polo with a ribbed collar and two-button placket. Moisture-wicking fabric designed for warm days.'],
            ['V-Neck Cotton Tee', 2799, 55, 'Men', 'T-Shirts', 'vneck-tee', 'Classic V-neck tee cut from heavyweight cotton. A wardrobe staple with a relaxed, clean fit.'],
            ['Oxford Casual Shirt', 4999, 35, 'Men', 'Shirts', 'oxford-shirt', 'Instagram-ready Oxford shirt in brushed cotton. Button-down collar works for office or weekend.'],
            ['Linen Button-Up Shirt', 6499, 30, 'Men', 'Shirts', 'linen-shirt', 'Lightweight linen shirt that breathes in the heat. Relaxed fit with natural crinkle texture.'],
            ['Flannel Check Shirt', 5999, 32, 'Men', 'Shirts', 'flannel-shirt', 'Brushed flannel shirt in a classic check pattern. Soft, warm and perfect for layering.'],
            ['Slim Fit Denim Jeans', 7999, 48, 'Men', 'Jeans', 'slim-denim', 'Slim-fit jeans in stretch washed denim. Mid-rise with a tapered leg for a modern silhouette.'],
            ['Straight Leg Denim Jeans', 7499, 42, 'Men', 'Jeans', 'straight-jeans', 'Timeless straight-leg jeans in premium rigid denim. Five-pocket styling with a lived-in finish.'],
            ['Ripped Skinny Jeans', 8499, 38, 'Men', 'Jeans', 'ripped-jeans', 'Skinny jeans with distressed rips and faded detailing. High stretch fabric for all-day comfort.'],
            ['Leather Biker Jacket', 24999, 15, 'Men', 'Jackets', 'biker-jacket', 'Iconic biker jacket in smooth faux leather with asymmetric zip and quilted panels.'],
            ['Denim Trucker Jacket', 9999, 22, 'Men', 'Jackets', 'trucker-jacket', 'Vintage-inspired trucker jacket in washed denim. Snap buttons, chest pockets and a durable finish.'],
            ['Bomber Jacket', 11999, 20, 'Men', 'Jackets', 'bomber-jacket', 'Classic bomber with ribbed cuffs and hem. Lightweight shell in a versatile matte finish.'],
            ['Summer Floral Midi Dress', 7999, 36, 'Women', 'Dresses', 'floral-dress', 'Flowy floral midi dress in a lightweight viscose. Elastic waist with short flutter sleeves.'],
            ['Little Black Dress', 6499, 40, 'Women', 'Dresses', 'little-black-dress', 'The essential LBD with a figure-flattering cut. Pairs effortlessly with heels or sneakers.'],
            ['Wrap Maxi Dress', 8999, 28, 'Women', 'Dresses', 'maxi-dress', 'Elegant wrap maxi dress with a tie waist and full skirt. Flattering neckline and soft jersey drape.'],
            ['Cocktail Bodycon Dress', 9499, 25, 'Women', 'Dresses', 'bodycon-dress', 'Sleek bodycon dress with a squared neckline. Stretch knit hugs your curves for a polished look.'],
            ['Casual Blouse', 4499, 44, 'Women', 'Tops', 'casual-blouse', 'Lightweight blouse with a relaxed cut and subtle button placket. Transitions from desk to dinner.'],
            ['Off-Shoulder Top', 3799, 50, 'Women', 'Tops', 'offshoulder-top', 'Romantic off-shoulder top with elasticated neckline. Airy cotton-blend for breezy summer days.'],
            ['Knit Sweater Top', 5499, 33, 'Women', 'Tops', 'knit-sweater', 'Chunky-knit sweater top in a cozy blend. Chunky ribbed cuffs and a soft, plush hand-feel.'],
            ['Pleated Mini Skirt', 4999, 37, 'Women', 'Skirts', 'pleated-skirt', 'Flattering pleated mini skirt with an elastic waistband. Moves beautifully with every step.'],
            ['A-Line Denim Skirt', 5799, 30, 'Women', 'Skirts', 'denim-skirt', 'A-line denim skirt with a button fly and frayed hem. A fresh take on a classic staple.'],
            ['High-Waist Yoga Leggings', 4999, 58, 'Women', 'Leggings', 'yoga-leggings', 'Squat-proof high-waist leggings with four-way stretch. Designed to move with you through every workout.'],
            ['Fleece-Lined Leggings', 4599, 46, 'Women', 'Leggings', 'fleece-leggings', 'Winter-ready leggings with brushed fleece inside. High waistband stays put for all-day comfort.'],
        ];

        foreach ($products as [$title, $price, $quantity, $parentName, $childName, $image, $description]) {
            $product = Product::updateOrCreate(
                ['title' => $title],
                [
                    'description' => $description,
                    'price' => $price,
                    'quantity' => $quantity,
                    'published' => true,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );

            $product->categories()->syncWithoutDetaching([$categoryIds[$childName]]);

            if (!$product->images()->exists()) {
                $path = 'images/products/' . $image . '.jpg';

                // Demo installs don't ship per-product photos; fall back to the
                // category artwork so seeded cards never show a broken image.
                if (!file_exists(storage_path('app/public/' . $path))) {
                    foreach (['jpg', 'png'] as $extension) {
                        $fallback = 'categories/' . Str::slug($childName) . '.' . $extension;
                        if (file_exists(storage_path('app/public/' . $fallback))) {
                            $path = $fallback;
                            break;
                        }
                    }
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'url' => config('app.url') . '/storage/' . $path,
                    'mime' => 'image/jpeg',
                    'size' => file_exists(storage_path('app/public/' . $path))
                        ? filesize(storage_path('app/public/' . $path))
                        : 0,
                    'position' => 1,
                ]);
            }
        }
    }
}