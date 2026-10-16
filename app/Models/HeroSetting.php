<?php

// app/Models/HeroSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HeroSetting extends Model implements HasMedia
{
    use InteractsWithMedia {
        getFirstMediaUrl as getFirstMediaUrlTrait;
    }

    protected $fillable = [
        'doctor_name',
        'doctor_title',
        'doctor_university',
        'badge_text',
        'whatsapp_number',
        'whatsapp_button_text',
        'booking_button_text',
        'years_experience',
        'surgeries_count',
        'patients_count',
        'stats_label_1',
        'stats_label_2',
        'stats_label_3',
        'draft_data',
        'facebook_url',
        'youtube_url',
        'instagram_url',
        'tiktok_url',
        'twitter_url',
    ];

    protected $casts = [
        'draft_data' => 'array',
    ];

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $field = $collectionName === 'doctor-photo' ? 'doctor_photo' : ($collectionName === 'logo' ? 'logo' : null);

        if ($field && ! empty($this->{$field})) {
            $imageFile = $this->{$field};
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
