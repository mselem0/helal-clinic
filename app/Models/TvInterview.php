<?php

namespace App\Models;

use App\Support\YouTubeVideo;
use Illuminate\Database\Eloquent\Model;

class TvInterview extends Model
{
    protected $fillable = ['title', 'description', 'youtube_url', 'cover_path', 'is_active', 'order'];

    protected $casts = ['is_active' => 'boolean'];

    public function getYoutubeIdAttribute(): ?string
    {
        return YouTubeVideo::id($this->youtube_url);
    }
}
