<?php

namespace App\Support;

use App\Models\SitePage;
use App\Services\SiteView;

class ContentUrl
{
    public static function resolve(?string $value): string
    {
        $value = trim($value ?? '');
        if ($value === 'assets/logo.png' && is_file(public_path('assets/logo-small.webp'))) {
            $value = 'assets/logo-small.webp';
        }
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

        $url = asset(ltrim($value, '/'));
        if (in_array($value, ['assets/doctor-conference.jpg', 'assets/doctor-conference.webp'], true) || (str_starts_with($value, 'assets/') && str_ends_with($value, '.mp4') && is_file(public_path($value)))) {
            $url .= '?v='.substr(hash_file('sha256', public_path($value)), 0, 12);
        }

        return $url;
    }
}
