<?php

// app/Models/AboutDoctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AboutDoctor extends Model
{
    protected $fillable = [
        'bio_title',
        'bio_text',
        'years_experience',
        'surgeries_count',
        'patients_count',
        'stats_label_1',
        'stats_label_2',
        'stats_label_3',
        'draft_data',
    ];

    protected $casts = [
        'draft_data' => 'array',
    ];

    public function credentials(): HasMany
    {
        return $this->hasMany(DoctorCredential::class)->orderBy('order');
    }

    public function getSafeBioTextAttribute(): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $this->bio_text ?? '') ?? '';
        $html = strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li>');

        return preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $html) ?? '';
    }
}
