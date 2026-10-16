<?php

// app/Models/SeoRedirect.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SeoRedirect extends Model
{
    protected static function booted(): void
    {
        static::saving(function ($redirect) {
            if (! $redirect->is_active) {
                return;
            }
            $source = static::localPath($redirect->source_url);
            $target = static::localPath($redirect->target_url);
            $seen = [$source];
            for ($i = 0; $i < 30; $i++) {
                if (in_array($target, $seen, true)) {
                    throw ValidationException::withMessages(['target_url' => 'التحويل يؤدي إلى حلقة مغلقة.']);
                }
                $seen[] = $target;
                $next = static::where('source_url', $target)->where('is_active', true)->where('id', '!=', $redirect->id ?? 0)->first();
                if (! $next) {
                    return;
                }
                $target = static::localPath($next->target_url);
            }
            throw ValidationException::withMessages(['target_url' => 'سلسلة التحويلات طويلة جدًا.']);
        });
    }

    private static function localPath(string $url): string
    {
        $parts = parse_url($url);
        if (! $parts) {
            return $url;
        }
        if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower(parse_url(url('/'), PHP_URL_HOST) ?? '') || ($parts['port'] ?? null) !== parse_url(url('/'), PHP_URL_PORT))) {
            return $url;
        }

        return '/'.ltrim(rawurldecode($parts['path'] ?? '/'), '/');
    }

    protected $fillable = [
        'source_url',
        'target_url',
        'redirect_type',
        'match_type',
        'hit_count',
        'last_accessed_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
