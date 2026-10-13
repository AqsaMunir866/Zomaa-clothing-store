<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public const CACHE_KEY = 'business_settings';

    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::pluck('value', 'key')->filter(fn ($value) => $value !== null && $value !== '')->all();
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function businessName(): string
    {
        return (string) self::get('business_name', config('app.name', 'Maison Blanc'));
    }

    public static function tagline(): string
    {
        return (string) self::get('tagline', 'Timeless essentials, crafted in soft neutrals for mindful living.');
    }

    public static function announcement(): string
    {
        return (string) self::get('announcement', 'Complimentary delivery on orders over Rs 5,000');
    }

    public static function logoUrl(): ?string
    {
        $path = self::get('logo_path');

        return $path ? asset('storage/' . $path) : null;
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}