<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Service;
use App\Support\ReviewedArticleLinks;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewedArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $articles = json_decode(file_get_contents(database_path('content/reviewed-articles.json')), true, flags: JSON_THROW_ON_ERROR);
        $defaults = json_decode(file_get_contents(database_path('content/collections.json')), true, flags: JSON_THROW_ON_ERROR)['articles'];
        $serviceLinks = ReviewedArticleLinks::services();
        $serviceIds = Service::whereIn('slug', collect($serviceLinks)->pluck(1))->pluck('id', 'slug');

        DB::transaction(function () use ($articles, $defaults, $serviceLinks, $serviceIds): void {
            foreach ($articles as $index => $data) {
                // Existing bundled URLs remain stable. Never overwrite editorial changes.
                $slug = $index < 3 ? 'article-'.($index + 1) : $data['slug'];
                $existing = Article::where('slug', $slug)->first();
                $customMetadata = $existing && collect(['meta_title', 'category', 'author_name', 'medical_reviewer', 'published_at', 'reviewed_at', 'sources', 'service_ids'])->contains(fn ($field) => filled($existing->{$field}));
                if ($existing && ($index >= 3 || $existing->title !== $defaults[$index]['title'] || $existing->content !== $defaults[$index]['description'] || $existing->meta_description !== $defaults[$index]['description'] || $existing->image_path !== $defaults[$index]['image'] || $customMetadata || $existing->is_published || $existing->getMedia('article-image')->isNotEmpty())) {
                    continue;
                }

                $data['slug'] = $slug;
                if (isset($serviceLinks[$slug]) && $data['title'] === $serviceLinks[$slug][0]) {
                    $serviceId = $serviceIds->get($serviceLinks[$slug][1]);
                    if ($serviceId) {
                        $data['service_ids'] = [(int) $serviceId];
                    }
                }
                $data['is_published'] = true;
                $data['published_at'] = now();
                // Source verification is not a named clinician's medical review.
                $data['author_name'] = null;
                $data['medical_reviewer'] = null;
                $data['reviewed_at'] = null;
                ($existing ?? new Article)->fill($data)->save();
            }
        });
    }
}
