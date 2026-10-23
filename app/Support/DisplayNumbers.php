<?php

namespace App\Support;

final class DisplayNumbers
{
    public static function text(string $value): string
    {
        $value = strtr($value, array_combine(
            preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789'),
        ));

        return preg_replace_callback('/(?<=[0-9])[٫٬](?=[0-9])/u', fn ($match) => $match[0] === '٫' ? '.' : ',', $value);
    }

    public static function html(string $html): string
    {
        // Leave markup, URLs, structured data and executable code untouched.
        $protected = <<<'REGEX'
~<script\b[^>]*>.*?</script\s*>(*SKIP)(*F)|<style\b[^>]*>.*?</style\s*>(*SKIP)(*F)|<!--.*?-->(*SKIP)(*F)|<(?:[^>"']|"[^"]*"|'[^']*')*>(*SKIP)(*F)|[^<]+~isu
REGEX;

        return preg_replace_callback($protected, fn ($match) => self::text($match[0]), $html);
    }
}
