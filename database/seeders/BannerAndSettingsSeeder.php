<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Setting;
use App\Support\Settings as SettingsHelper;
use Illuminate\Database\Seeder;

class BannerAndSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'business_name' => 'Maison Blanc',
            'tagline' => 'Timeless essentials, crafted in soft neutrals for mindful living.',
            'email' => 'hello@maisonblanc.pk',
            'phone' => '+92 300 0000000',
            'primary_address' => 'Shop 12, Gulberg III, Lahore, Pakistan',
            'announcement' => 'Complimentary delivery on orders over Rs 5,000',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        SettingsHelper::flushCache();

        $heroBanners = [
            [
                'title' => 'Soft Cotton, Quietly Defined',
                'kicker' => 'The Essential Edit',
                'text' => 'Airy essentials in neutral tones, made to be lived in.',
                'link_label' => 'Shop T-Shirts',
                'link_url' => '/category/t-shirts',
                'image_slug' => 't-shirts',
            ],
            [
                'title' => 'Tailored Layers For New Seasons',
                'kicker' => 'Layer With Intention',
                'text' => 'Structured jackets and relaxed overshirts, cut for effortless layering.',
                'link_label' => 'Shop Jackets',
                'link_url' => '/category/jackets',
                'image_slug' => 'jackets',
            ],
            [
                'title' => 'Fluid Silhouettes In Soft Ivory',
                'kicker' => 'The Dress Edit',
                'text' => 'Considered cuts that move with you, from day to evening.',
                'link_label' => 'Shop Dresses',
                'link_url' => '/category/dresses',
                'image_slug' => 'dresses',
            ],
        ];

        foreach ($heroBanners as $index => $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title'], 'position' => 'hero'],
                [
                    'kicker' => $banner['kicker'],
                    'text' => $banner['text'],
                    'link_label' => $banner['link_label'],
                    'link_url' => $banner['link_url'],
                    'image_url' => asset('storage/categories/' . $banner['image_slug'] . '.jpg'),
                    'position' => 'hero',
                    'sort_order' => $index,
                    'active' => true,
                ]
            );
        }

        $splitBanners = [
            [
                'title' => 'The Menswear Edit',
                'kicker' => 'Understated essentials',
                'text' => 'Crisp shirts, washed denim and soft knits.',
                'link_label' => 'Shop Men',
                'link_url' => '/category/men',
                'image_slug' => 'men',
            ],
            [
                'title' => 'The Womenswear Edit',
                'kicker' => 'Effortless layering',
                'text' => 'Fluid dresses, effortless tops and tailored separates.',
                'link_label' => 'Shop Women',
                'link_url' => '/category/women',
                'image_slug' => 'women',
            ],
        ];

        foreach ($splitBanners as $index => $banner) {
            Banner::updateOrCreate(
                ['title' => $banner['title'], 'position' => 'split'],
                [
                    'kicker' => $banner['kicker'],
                    'text' => $banner['text'],
                    'link_label' => $banner['link_label'],
                    'link_url' => $banner['link_url'],
                    'image_url' => asset('storage/categories/' . $banner['image_slug'] . '.png'),
                    'position' => 'split',
                    'sort_order' => $index,
                    'active' => true,
                ]
            );
        }
    }
}