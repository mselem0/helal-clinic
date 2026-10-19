<?php

namespace App\Support;

class VideoSource
{
    public const VIMEO = [
        '2129128044360416' => '1236786561',
        '2013840505981997' => '1236786559',
        '1683918787070180' => '1236786562',
        '1122351437148430' => '1236786560',
    ];

    public static function resolve(?string $source, ?string $reel = null): string
    {
        $source = trim($source ?? '');
        // Local previews should not depend on third-party embed availability.
        if (app()->environment('local') && $reel && isset(self::VIMEO[$reel])) {
            $local = 'assets/reel-'.$reel.'.mp4';
            $mapped = 'https://player.vimeo.com/video/'.self::VIMEO[$reel];
            if (($source === '' || $source === $local || $source === $mapped || $source === 'https://vimeo.com/'.self::VIMEO[$reel]) && is_file(public_path($local))) {
                return url('/local-video/'.$reel.'.mp4');
            }
        }

        if ($reel && ($source === '' || $source === 'assets/reel-'.$reel.'.mp4') && isset(self::VIMEO[$reel])) {
            return 'https://player.vimeo.com/video/'.self::VIMEO[$reel];
        }
        if (preg_match('~^https://(?:www\.)?(?:vimeo\.com/|player\.vimeo\.com/video/)(\d+)(?:[/?#]|$)~', $source, $match)) {
            $hash = parse_url($source, PHP_URL_QUERY);
            parse_str($hash ?? '', $query);

            return 'https://player.vimeo.com/video/'.$match[1].(isset($query['h']) && ctype_alnum($query['h']) ? '?h='.$query['h'] : '');
        }

        return ContentUrl::resolve($source);
    }
}
