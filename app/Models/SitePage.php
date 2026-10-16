<?php

namespace App\Models;

use App\Support\TracksPublicSlug;
use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    use TracksPublicSlug;

    protected $guarded = ['id'];

    protected $casts = ['sections' => 'array', 'draft_data' => 'array', 'is_published' => 'boolean', 'published_at' => 'datetime'];

    public function publicPath(): string
    {
        return $this->page_key === 'home' ? '/' : '/'.$this->slug;
    }

    public function getUrlAttribute(): string
    {
        return url($this->publicPath());
    }
}
