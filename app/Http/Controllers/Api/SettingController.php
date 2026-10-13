<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Support\Settings as SettingsHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                Storage::deleteDirectory('/public/' . dirname($oldPath));
            }

            $path = 'logos/' . Str::random(40);
            if (!Storage::exists($path)) {
                Storage::makeDirectory($path, 0755, true);
            }
            $name = Str::random().'.'.$request->file('logo')->getClientOriginalExtension();
            if (!Storage::putFileAs('public/' . $path, $request->file('logo'), $name)) {
                throw new \Exception('Unable to save logo file');
            }

            Setting::updateOrCreate(
                ['key' => 'logo_path'],
                ['value' => $path . '/' . $name]
            );
        } elseif ($request->boolean('remove_logo')) {
            $oldPath = SettingsHelper::get('logo_path');
            if ($oldPath) {
                Storage::deleteDirectory('/public/' . dirname($oldPath));
            }
            Setting::updateOrCreate(['key' => 'logo_path'], ['value' => null]);
        }

        SettingsHelper::flushCache();

        return response()->json([
            'data' => SettingsHelper::all() + ['logo_url' => SettingsHelper::logoUrl()],
        ]);
    }
}