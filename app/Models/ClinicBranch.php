<?php

// app/Models/ClinicBranch.php

namespace App\Models;

use App\Support\TracksPublicSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicBranch extends Model
{
    use HasFactory;
    use TracksPublicSlug;

    public function publicPath(): string
    {
        return '/branches/'.$this->slug;
    }

    protected $fillable = [
        'og_image', 'slug', 'description', 'directions', 'service_ids', 'meta_title', 'meta_description',
        'name',
        'address',
        'phone',
        'working_hours',
        'google_maps_url',
        'google_maps_embed',
        'is_active',
        'booking_schedule_enabled', 'weekly_schedule', 'schedule_exceptions',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean', 'service_ids' => 'array',
        'booking_schedule_enabled' => 'boolean', 'weekly_schedule' => 'array', 'schedule_exceptions' => 'array',
    ];
}
