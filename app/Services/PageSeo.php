<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\SeoPage;
use App\Models\SitePage;
use App\Models\Video;
use App\Support\ContentUrl;
use App\Support\VideoSource;

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
            ? ($item->meta_description ?: ($item instanceof Article ? $item->content : $item->description) ?: ($item instanceof ClinicBranch ? 'عيادة د. محمد هلال — '.$item->name.'. العنوان: '.$item->address : null))
            : ($legacy?->meta_description ?: $page->meta_description ?: $page->intro);
        $description = str(strip_tags($description ?? ''))->squish()->limit(170, '')->toString();
        $canonical = $item ? url($item->publicPath()) : ($legacy?->canonical_url ?: $page->canonical_url ?: SiteView::pageUrl($page));
        $canonical = explode('#', $canonical, 2)[0];
        if (! $item && $page->page_key === 'articles' && request()->integer('page', 1) > 1) {
            [$base, $query] = array_pad(explode('?', $canonical, 2), 2, '');
            parse_str($query, $parameters);
            $parameters['page'] = request()->integer('page');
            $canonical = $base.'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
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
        if (in_array($page->page_key, ['home', 'about'], true)) {
            $about = SiteView::draft(SitePage::where('page_key', 'about')->where('is_published', true)->first());
            $doctor = ['@type' => ['Person', 'Physician'], '@id' => url('/').'#doctor', 'name' => 'د. محمد هلال', 'url' => $about ? SiteView::pageUrl($about) : url('/')];
            // Full credentials are visible on the about page only, and come from its editor.
            if ($page->page_key === 'about') {
                $profile = $page->doctor_profile ?? [];
                if (trim($profile['headline'] ?? '') !== '') {
                    $doctor['jobTitle'] = $profile['headline'];
                }
                $credentials = [];
                foreach ($profile['qualifications'] ?? [] as $qualification) {
                    if (trim($qualification['title'] ?? '') !== '') {
                        $credential = ['@type' => 'EducationalOccupationalCredential', 'name' => $qualification['title']];
                        if (trim($qualification['detail'] ?? '') !== '') {
                            $credential['description'] = $qualification['detail'];
                        }
                        $credentials[] = $credential;
                    }
                }
                if ($credentials) {
                    $doctor['hasCredential'] = $credentials;
                }
                $memberships = array_values(array_filter(array_map(fn ($membership) => trim($membership['title'] ?? '') !== '' ? ['@type' => 'Organization', 'name' => $membership['title']] : null, $profile['memberships'] ?? [])));
                if ($memberships) {
                    $doctor['memberOf'] = $memberships;
                }
            }
            $schema[] = $doctor;
        }
        if ($item instanceof ClinicBranch) {
            $branch = ['@type' => 'MedicalClinic', '@id' => $canonical.'#clinic', 'name' => $item->name, 'url' => $canonical];
            if (trim($item->address ?? '') !== '') {
                $branch['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $item->address];
            }
            if (trim($item->phone ?? '') !== '') {
                $branch['telephone'] = $item->phone;
            }
            if (filter_var($item->google_maps_url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($item->google_maps_url, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true)) {
                $branch['hasMap'] = $item->google_maps_url;
            }
            $schema[] = $branch;
        }
        if ($item instanceof Article) {
            $article = ['@type' => 'Article', '@id' => $canonical.'#article', 'headline' => $item->title, 'description' => $description, 'image' => $image, 'mainEntityOfPage' => $canonical, 'dateModified' => $item->updated_at?->toIso8601String()];
            if ($item->published_at) {
                $article['datePublished'] = $item->published_at->toIso8601String();
            }
            if ($item->author_name) {
                $article['author'] = ['@type' => 'Person', 'name' => $item->author_name];
            }
            $citations = [];
            foreach ($item->sources ?? [] as $source) {
                $url = $source['url'] ?? '';
                if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) {
                    $citation = ['@type' => 'CreativeWork', 'url' => $url];
                    if (trim($source['title'] ?? '') !== '') {
                        $citation['name'] = $source['title'];
                    }
                    $citations[] = $citation;
                }
            }
            if ($citations) {
                $article['citation'] = $citations;
            }
            $medicalPage = ['@type' => 'MedicalWebPage', '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $item->title, 'inLanguage' => 'ar-EG', 'mainEntity' => ['@id' => $canonical.'#article']];
            if (trim($item->medical_reviewer ?? '') !== '') {
                $medicalPage['reviewedBy'] = ['@type' => 'Person', 'name' => $item->medical_reviewer];
            }
            if ($item->reviewed_at && $item->reviewed_at->lte(now())) {
                $medicalPage['lastReviewed'] = $item->reviewed_at->toDateString();
            }
            $schema[] = $article;
            $schema[] = $medicalPage;
        }
        $videoSections = collect($page->sections ?? [])->map(fn ($section) => is_array($section) ? ($section['key'] ?? '') : $section);
        if ($page->page_key === 'videos' && $videoSections->contains('videos') && SiteView::draft(PageSection::where('section_key', 'videos')->first())?->is_active) {
            foreach (Video::where('is_active', true)->get() as $video) {
                $thumb = ContentUrl::resolve($video->getFirstMediaUrl('video-thumbnail') ?: $video->image_path);
                if ($thumb && ! str_starts_with($thumb, 'http')) {
                    $thumb = url($thumb);
                }
                $source = VideoSource::resolve($video->external_video_url ?: ($video->getFirstMediaUrl('video-file') ?: $video->video_path), $video->reel);
                // Do not invent an upload date: incomplete video metadata is not emitted.
                if (trim((string) $video->title) === '' || ! $thumb || ! $source || ! $video->uploaded_at?->lte(now())) {
                    continue;
                }
                $object = ['@type' => 'VideoObject', 'name' => $video->title, 'description' => $video->description, 'thumbnailUrl' => $thumb, 'uploadDate' => $video->uploaded_at->toIso8601String()];
                if (str_starts_with($source, 'https://player.vimeo.com/video/')) {
                    $object['embedUrl'] = $source;
                } else {
                    $object['contentUrl'] = $source;
                }
                $schema[] = $object;
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
