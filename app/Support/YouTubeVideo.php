<?php

namespace App\Support;

class YouTubeVideo
{
    public static function id(?string $url): ?string
    {
        if (! is_string($url) || strlen($url) > 500) {
            return null;
        }

        $url = trim($url);
        if (preg_match('~^https://(?:www\.)?youtu\.be/([A-Za-z0-9_-]{11})(?:[?#].*)?$~i', $url, $match)
            || preg_match('~^https://(?:www\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})(?:[&#].*)?$~i', $url, $match)
            || preg_match('~^https://(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{11})(?:[?#].*)?$~i', $url, $match)
            || preg_match('~^https://(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})(?:[?#].*)?$~i', $url, $match)) {
            return $match[1];
        }

        return null;
    }
}
