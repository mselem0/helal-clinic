<?php

namespace App\Models;

use App\Support\TracksPublicSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Article extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use TracksPublicSlug;

    public function publicPath(): string
    {
        return '/blog/'.$this->slug;
    }

    protected $casts = ['sources' => 'array', 'service_ids' => 'array', 'published_at' => 'datetime', 'reviewed_at' => 'date', 'is_published' => 'boolean'];

    protected $fillable = [
        'og_image', 'author_name', 'medical_reviewer', 'published_at', 'reviewed_at', 'sources', 'service_ids',
        'image_path',
        'category',

        'title',
        'slug',
        'content',
        'is_published',
        'meta_title',
        'meta_description',
    ];
}
