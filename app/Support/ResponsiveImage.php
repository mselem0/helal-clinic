<?php

namespace App\Support;

class ResponsiveImage
{
    public static function srcset(?string $source): string
    {
        $url = ContentUrl::resolve($source);
        if (parse_url($url, PHP_URL_HOST) !== parse_url(url('/'), PHP_URL_HOST)) {
            return '';
        }
        $path = ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
        if (! preg_match('~^assets/([a-zA-Z0-9_-]+)\.(?:jpe?g|png|webp)$~', $path, $matches)) {
            return '';
        }
        $variants = [];
        foreach ([320, 640, 960] as $width) {
            $file = 'assets/'.$matches[1].'-'.$width.'.webp';
            if (is_file(public_path($file))) {
                $variants[] = asset($file).' '.$width.'w';
            }
        }

        return implode(', ', $variants);
    }
}
