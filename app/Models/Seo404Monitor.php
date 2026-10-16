<?php

// app/Models/Seo404Monitor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seo404Monitor extends Model
{
    protected $fillable = [
        'url',
        'referer_url',
        'user_agent',
        'ip_address',
        'hit_count',
        'is_resolved',
        'seo_redirect_id',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function redirect(): BelongsTo
    {
        return $this->belongsTo(SeoRedirect::class, 'seo_redirect_id');
    }
}
