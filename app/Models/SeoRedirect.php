<?php

// app/Models/SeoRedirect.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
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
