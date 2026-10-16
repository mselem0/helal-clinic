<?php

namespace App\Support;

use App\Models\SitePage;
use App\Services\SiteView;

class ContentUrl
{
    public static function resolve(?string $value): string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'assets/') && str_ends_with(strtolower($value), '.jpg') && is_file(public_path(substr($value, 0, -4).'.webp'))) {
            $value = substr($value, 0, -4).'.webp';
        }
        if (str_starts_with($value, '//') || preg_match('/[\x00-\x20]/', $value)) {
            return '';
        }
        if (str_starts_with($value, '#')) {
            $key = ['#home' => 'home', '#about' => 'about', '#your-journey' => 'journey', '#services' => 'services', '#videos' => 'videos', '#articles' => 'articles', '#clinics' => 'branches', '#faq' => 'faq', '#booking' => 'appointment'][$value] ?? null;
            if ($key && ($page = SiteView::draft(SitePage::where('page_key', $key)->first()))) {
                return SiteView::pageUrl($page);
            }

            return request()->is('/') ? $value : url('/').$value;
        }
        if (preg_match('~^(https?://|tel:|mailto:|#)~i', $value)) {
            return $value;
        }
        if (str_contains($value, ':') || str_contains($value, '..')) {
            return '';
        }
        if (str_starts_with($value, 'landing/')) {
            return asset('storage/'.$value);
        }

        return asset(ltrim($value, '/'));
    }
}
