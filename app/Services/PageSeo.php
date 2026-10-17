<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\SitePage;
use App\Models\Video;
use App\Support\ContentUrl;

class PageSeo
{
    public static function data(SitePage $page, $item = null): array
    {
        $legacy = SeoPage::where('page_key', $page->page_key)->first();
        if (SiteView::preview() && $legacy) {
            $legacy = clone $legacy;
            $legacy->forceFill(array_replace(collect($page->draft_data ?? [])->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'robots'])->all(), $legacy->draft_data ?? []));
        }
        $title = $item?->meta_title ?: ($item ? ($item->title ?? $item->name).' | عيادات د. محمد هلال' : ($legacy?->meta_title ?: $page->meta_title ?: $page->title.' | عيادات د. محمد هلال'));
        $description = $item
            ? ($item->meta_description ?: $item->description ?: ($item instanceof ClinicBranch ? 'عيادة د. محمد هلال — '.$item->name.'. العنوان: '.$item->address : null))
            : ($legacy?->meta_description ?: $page->meta_description ?: $page->intro);
        $description = str(strip_tags($description ?? ''))->squish()->limit(170, '')->toString();
        $canonical = $item ? url($item->publicPath()) : ($legacy?->canonical_url ?: $page->canonical_url ?: SiteView::pageUrl($page));
        if (request()->integer('page', 1) > 1) {
            $canonical .= '?page='.request()->integer('page');
        }
        $image = $item?->og_image;
        if (! $image && $item && method_exists($item, 'getFirstMediaUrl')) {
            $image = $item->getFirstMediaUrl($item instanceof Article ? 'article-image' : 'service-image');
        }
        if (! $item) {
            $image = $legacy?->og_image ?: $page->og_image;
        }
        $image = ContentUrl::resolve($image ?: $item?->image_path ?: 'assets/cover.jpg');
        if (! str_starts_with($image, 'http')) {
            $image = url($image);
        }
        $robots = SiteView::preview() ? 'noindex,nofollow' : str_replace('_', ',', $legacy?->robots ?: $page->robots ?: 'index_follow');
        $schema = [['@type' => 'WebSite', '@id' => url('/').'#website', 'name' => 'عيادات د. محمد هلال', 'url' => url('/'), 'inLanguage' => 'ar-EG']];
        if ($page->page_key === 'home' || $page->page_key === 'about') {
            $schema[] = ['@type' => 'Physician', '@id' => url('/').'#doctor', 'name' => 'د. محمد هلال', 'url' => SitePage::where('page_key', 'about')->first()?->url ?: url('/')];
        }
        if ($item instanceof ClinicBranch) {
            $branch = ['@type' => 'MedicalClinic', '@id' => $canonical.'#clinic', 'name' => $item->name, 'url' => $canonical, 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $item->address, 'addressCountry' => 'EG']];
            if ($item->phone) {
                $branch['telephone'] = $item->phone;
            }
            if ($item->google_maps_url) {
                $branch['hasMap'] = $item->google_maps_url;
            }
            $schema[] = $branch;
        }
        if ($item instanceof Article) {
            $article = ['@type' => 'Article', 'headline' => $item->title, 'description' => $description, 'image' => $image, 'mainEntityOfPage' => $canonical, 'dateModified' => $item->updated_at?->toIso8601String()];
            if ($item->published_at) {
                $article['datePublished'] = $item->published_at->toIso8601String();
            }
            if ($item->author_name) {
                $article['author'] = ['@type' => 'Person', 'name' => $item->author_name];
            }
            $schema[] = $article;
        }
        if ($page->page_key === 'videos') {
            foreach (Video::where('is_active', true)->get() as $video) {
                $thumb = $video->getFirstMediaUrl('video-thumbnail') ?: $video->image_path;
                $file = $video->getFirstMediaUrl('video-file') ?: $video->video_path;
                // Do not invent an upload date: incomplete video metadata is not emitted.
                if ($thumb && $file && $video->uploaded_at) {
                    $schema[] = ['@type' => 'VideoObject', 'name' => $video->title, 'description' => $video->description, 'thumbnailUrl' => ContentUrl::resolve($thumb), 'contentUrl' => ContentUrl::resolve($file), 'uploadDate' => $video->uploaded_at->toIso8601String()];
                }
            }
        }
        if ($page->page_key !== 'home') {
            $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => url('/')]];
            if ($item) {
                $type = $page->page_key;
                $crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => match ($type) {
                    'service' => 'الخدمات','article' => 'المقالات',default => 'الفروع'
                }, 'item' => url(match ($type) {
                    'service' => '/services','article' => '/blog',default => '/branches'
                })];
            }
            $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => $page->title, 'item' => $canonical];
            $schema[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
        }

        return ['meta_title' => $title, 'meta_description' => $description, 'canonical_url' => $canonical, 'robots' => $robots, 'og_title' => $item ? $title : ($legacy?->og_title ?: $title), 'og_description' => $item ? $description : ($legacy?->og_description ?: $description), 'og_image' => $image, 'og_type' => $item instanceof Article ? 'article' : 'website', 'schema' => ['@context' => 'https://schema.org', '@graph' => $schema]];
    }
}
