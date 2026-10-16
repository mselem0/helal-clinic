<?php

namespace Modules\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'sources',
        'tools_used',
        'tokens_used',
        'response_time_ms',
    ];

    protected $casts = [
        'sources' => 'array',
        'tools_used' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
