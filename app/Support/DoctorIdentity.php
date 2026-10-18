<?php

namespace App\Support;

class DoctorIdentity
{
    public static function links(array $profile): array
    {
        return collect($profile['official_links'] ?? [])
            ->filter(fn ($link) => is_array($link) && trim($link['label'] ?? '') !== '' && filter_var($link['url'] ?? '', FILTER_VALIDATE_URL) && strtolower(parse_url($link['url'], PHP_URL_SCHEME) ?? '') === 'https')
            ->unique('url')->values()->all();
    }
}
