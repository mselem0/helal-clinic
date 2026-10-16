<?php

// app/Helpers/SiteSettingHelper.php

namespace App\Helpers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingHelper
{
    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever("site_settings_{$key}", function () use ($key, $default) {
            $setting = SiteSetting::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, string $group = 'seo')
    {
        $setting = SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("site_settings_{$key}");

        return $setting;
    }

    public static function group(string $group): array
    {
        return SiteSetting::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function clearCache()
    {
        SiteSetting::all()->each(function ($setting) {
            Cache::forget("site_settings_{$setting->key}");
        });
    }

    public static function formatWhatsappNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            return '2'.$phone;
        }

        return $phone;
    }
}
