<?php

// app/Models/Service.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Service extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia {
        getFirstMediaUrl as getFirstMediaUrlTrait;
    }

    protected $fillable = [
        'image_path',
        'card_title',

        'name',
        'slug',
        'description',
        'rich_description',
        'meta_title',
        'meta_description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName === 'service-image' && ! empty($this->image)) {
            $imageFile = $this->image;
            if (filter_var($imageFile, FILTER_VALIDATE_URL) || str_starts_with($imageFile, 'http') || str_starts_with($imageFile, '/')) {
                return $imageFile;
            }
            if (file_exists(storage_path('app/public/'.$imageFile))) {
                return asset('storage/'.$imageFile);
            }
            if (file_exists(public_path($imageFile))) {
                return asset($imageFile);
            }
        }

        return $this->getFirstMediaUrlTrait($collectionName, $conversionName);
    }
}
