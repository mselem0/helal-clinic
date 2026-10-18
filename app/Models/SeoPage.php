<?php

// app/Models/SeoPage.php

namespace App\Models;

use App\Support\TracksPublishedUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SeoPage extends Model implements HasMedia
{
    use InteractsWithMedia;
    use TracksPublishedUpdate;

    protected $fillable = [
        'draft_data',
        'page_key',
        'page_label',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_card',
        'robots',
        'canonical_url',
        'focus_keywords',
        'seo_score',
        'score_breakdown',
        'readability_score',
    ];

    protected $casts = ['public_updated_at' => 'datetime',
        'draft_data' => 'array',
        'meta_keywords' => 'array',
        'focus_keywords' => 'array',
        'score_breakdown' => 'array',
    ];

    public function schemas(): HasMany
    {
        return $this->hasMany(SeoSchema::class);
    }
}
