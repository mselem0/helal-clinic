<?php

namespace App\Services;

use App\Models\SiteNavigation;
use App\Models\SitePage;
use Illuminate\Support\Facades\URL;

class SiteView
{
    public static function preview(): bool
    {
        return request()->boolean('preview') && request()->hasValidSignature();
    }

    public static function draft($model)
    {
        if ($model && static::preview()) {
            $model = clone $model;
            $model->forceFill($model->draft_data ?? []);
        }

        return \App\Support\AdsContent::model($model);
    }

    public static function pageUrl(SitePage $page): string
    {
        return match ($page->page_key) {
            'home' => route('home'),'services' => route('services'),'articles' => route('blog.index'),'branches' => route('branches'),default => $page->url
        };
    }

    public static function menu(string $location): array
    {
        $menu = static::draft(SiteNavigation::where('location', $location)->first());

        return collect($menu?->items ?? [])->map(function ($item) {
            $page = SitePage::where('page_key', $item['page_key'] ?? '')->first();
            if ($page && ! \App\Support\AdsContent::allowed($page)) {
                return null;
            }
            $page = static::draft($page);
            if (! $page?->is_published || ! \App\Support\AdsContent::allowed($page)) {
                return null;
            }
            $url = static::preview() ? URL::temporarySignedRoute('site.preview', now()->addMinutes(30), ['page' => $page->id, 'preview' => 1, 'theme' => request('theme', 'helal_classic')]) : static::pageUrl($page);

            return ['label' => \App\Support\AdsContent::clean($item['label']), 'url' => $url, 'key' => $page->page_key];
        })->filter()->values()->all();
    }
}
