<?php

// app/Models/SiteSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteSetting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'key',
        'value',
        'group',
        'draft_data',
    ];

    protected $casts = [
        'draft_data' => 'array',
    ];
}
