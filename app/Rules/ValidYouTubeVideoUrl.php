<?php

namespace App\Rules;

use App\Support\YouTubeVideo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidYouTubeVideoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! YouTubeVideo::id(is_string($value) ? $value : null)) {
            $fail('أدخلي رابط فيديو YouTube صالحًا.');
        }
    }
}
