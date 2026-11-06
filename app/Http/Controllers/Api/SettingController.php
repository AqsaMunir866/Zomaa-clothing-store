<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Support\MediaStorage;
use App\Support\Settings as SettingsHelper;

class SettingController extends Controller
{
    /**
     * Display all settings.
     */
    public function index()
    {
        return response()->json([
            'data' => SettingsHelper::all() + ['logo_url' => SettingsHelper::logoUrl()],
        ]);
    }

    /**
     * Update general business settings.
     */
    public function update(SettingsRequest $request)
    {
        $data = $request->validated();
        $allowed = ['business_name', 'tagline', 'email', 'phone', 'primary_address', 'announcement'];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $data[$key]]
                );
            }
        }

        if ($request->hasFile('logo')) {
            $oldPath = SettingsHelper::get('logo_path');
            if ($oldPath) {
                app(MediaStorage::class)->delete($oldPath);
            }

            // Logos go through the same driver as every other upload, so they
            // survive a redeploy instead of being written to the local disk.
            $media = app(MediaStorage::class)->put($request->file('logo'), 'logos');

            Setting::updateOrCreate(
                ['key' => 'logo_path'],
                ['value' => $media['path']]
            );
        } elseif ($request->boolean('remove_logo')) {
            $oldPath = SettingsHelper::get('logo_path');
            if ($oldPath) {
                app(MediaStorage::class)->delete($oldPath);
            }
            Setting::updateOrCreate(['key' => 'logo_path'], ['value' => null]);
        }

        SettingsHelper::flushCache();

        return response()->json([
            'data' => SettingsHelper::all() + ['logo_url' => SettingsHelper::logoUrl()],
        ]);
    }
}