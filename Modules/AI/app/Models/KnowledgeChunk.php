<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'source_type',
        'source_name',
        'content',
        'content_ar',
        'embedding',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
