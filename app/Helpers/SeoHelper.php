<?php

// app/Helpers/SeoHelper.php

namespace App\Helpers;

use App\Models\SeoGlobalSetting;
use App\Models\SeoPage;
use Illuminate\Support\Facades\Cache;

class SeoHelper
{
    public static function getSetting(string $key, $default = null)
    {
        return Cache::rememberForever("seo_global_settings_{$key}", function () use ($key, $default) {
            $setting = SeoGlobalSetting::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function setSetting(string $key, $value, string $group = 'general')
    {
        $setting = SeoGlobalSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("seo_global_settings_{$key}");

        return $setting;
    }

    public static function groupSettings(string $group): array
    {
        return SeoGlobalSetting::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function getPage(string $key): ?SeoPage
    {
        return Cache::rememberForever("seo_page_{$key}", function () use ($key) {
            return SeoPage::where('page_key', $key)->first();
        });
    }

    public static function clearCache()
    {
        SeoGlobalSetting::all()->each(function ($setting) {
            Cache::forget("seo_global_settings_{$setting->key}");
        });
        SeoPage::all()->each(function ($page) {
            Cache::forget("seo_page_{$page->page_key}");
        });
    }
}
