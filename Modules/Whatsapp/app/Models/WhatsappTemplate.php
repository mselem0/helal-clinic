<?php

// app/Models/WhatsappTemplate.php

namespace Modules\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsappTemplate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'category',
        'content',
        'variables_used',
        'is_active',
    ];

    protected $casts = [
        'variables_used' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($template) {
            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $template->content, $matches);
            $template->variables_used = array_unique($matches[1] ?? []);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'category',
                'content',
                'variables_used',
                'is_active',
            ])
            ->logOnlyDirty();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
