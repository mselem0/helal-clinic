<?php

namespace App\Support;

class ContentUrl
{
    public static function resolve(?string $value): string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, '//') || preg_match('/[\x00-\x20]/', $value)) {
            return '';
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
