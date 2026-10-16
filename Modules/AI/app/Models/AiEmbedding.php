<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiEmbedding extends Model
{
    protected $fillable = [
        'embeddable_type',
        'embeddable_id',
        'content_hash',
        'vector',
        'text_representation',
        'status',
        'error_message',
        'last_synced_at',
    ];

    protected $casts = [
        'vector' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the parent embeddable model.
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }
}
