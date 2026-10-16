<?php

// app/Models/SeoSchema.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoSchema extends Model
{
    protected $fillable = [
        'seo_page_id',
        'schema_type',
        'schema_data',
        'is_active',
        'is_primary',
    ];

    protected $casts = [
        'schema_data' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoPage::class, 'seo_page_id');
    }
}
