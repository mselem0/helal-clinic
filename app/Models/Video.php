<?php

// app/Models/Video.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Video extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as getFirstMediaUrlTrait;
    }

    protected $fillable = [
        'image_path',
        'video_path',
        'reel',
        'category',
        'description',

        'title',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName === 'video-file' && ! empty($this->video_file)) {
            $videoFile = $this->video_file;
            if (filter_var($videoFile, FILTER_VALIDATE_URL) || str_starts_with($videoFile, 'http') || str_starts_with($videoFile, '/')) {
                return $videoFile;
            }
            if (file_exists(storage_path('app/public/'.$videoFile))) {
                return asset('storage/'.$videoFile);
            }
            if (file_exists(public_path($videoFile))) {
                return asset($videoFile);
            }
        }

        return $this->getFirstMediaUrlTrait($collectionName, $conversionName);
    }
}
