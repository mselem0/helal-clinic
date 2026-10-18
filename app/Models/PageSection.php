<?php

// app/Models/PageSection.php

namespace App\Models;

use App\Support\TracksPublishedUpdate;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PageSection extends Model
{
    use LogsActivity;
    use TracksPublishedUpdate;

    protected $fillable = [
        'section_key',
        'section_label',
        'title',
        'subtitle',
        'body',
        'button_text',
        'button_url',
        'background_color',
        'background_image',
        'spacing',
        'order',
        'is_active',
        'draft_data',
        'published_at',
    ];

    protected $casts = ['public_updated_at' => 'datetime',
        'draft_data' => 'array',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'section_key',
                'section_label',
                'title',
                'subtitle',
                'body',
                'button_text',
                'button_url',
                'background_color',
                'background_image',
                'spacing',
                'order',
                'is_active',
            ])
            ->logOnlyDirty();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
