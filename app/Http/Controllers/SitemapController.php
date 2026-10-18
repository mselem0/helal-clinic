<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SitePage;
use App\Services\PageSeo;
use App\Services\SiteView;
use App\Support\ContentUrl;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function index(?string $part = null)
    {
        abort_unless(in_array(SeoHelper::getSetting('sitemap_enabled'), ['1', 1, true, 'true'], true), 404);
        $enabled = fn (string $key, string $default = '1'): bool => in_array(SeoHelper::getSetting($key, $default), ['1', 1, true, 'true'], true);
        $categoryKeys = ['home' => 'include_homepage', 'services' => 'include_services', 'articles' => 'include_articles', 'branches' => 'include_branches'];
        $entries = [];
        $images = $enabled('include_images_sitemap', '0');
        $videos = $enabled('include_videos_sitemap', '0');
        $add = function ($url, $date, $image = null, $videoObjects = []) use (&$entries, $images, $videos) {
            $entries[$url] = ['url' => $url, 'date' => $date, 'image' => $images ? ContentUrl::resolve($image) : '', 'videos' => $videos ? $videoObjects : []];
        };
        foreach (SitePage::where('is_published', true)->get() as $page) {
            if (! $enabled($categoryKeys[$page->page_key] ?? 'include_pages')) {
                continue;
            }
            $seo = SeoPage::where('page_key', $page->page_key)->first();
            $url = SiteView::pageUrl($page);
            if (str_contains($seo?->robots ?: $page->robots, 'noindex')) {
                continue;
            }
            $canonical = $seo?->canonical_url ?: $page->canonical_url;
            if ($canonical && rtrim($canonical, '/') !== rtrim($url, '/')) {
                continue;
            }
            $videoObjects = [];
            if ($videos && $page->page_key === 'videos') {
                $videoObjects = array_filter(PageSeo::data($page)['schema']['@graph'], fn ($node) => $node['@type'] === 'VideoObject' && trim(strip_tags($node['description'] ?? '')) !== '');
            }
            $date = collect([$page->public_updated_at, $seo?->public_updated_at])->filter()->sort()->last();
            $add($url, $date, $seo?->og_image ?: $page->og_image, $videoObjects);
        }
        foreach ([Service::class => 'services.show', Article::class => 'blog.show', ClinicBranch::class => 'branches.show'] as $class => $route) {
            [$setting, $legacyKey] = match ($class) {
                Service::class => ['include_services', 'service'],
                Article::class => ['include_articles', 'article'],
                ClinicBranch::class => ['include_branches', 'branch'],
            };
            if (! $enabled($setting) || str_contains(SeoPage::where('page_key', $legacyKey)->value('robots') ?? '', 'noindex')) {
                continue;
            }
            $query = $class::where($class === Article::class ? 'is_published' : 'is_active', true);
            if ($class === Article::class) {
                $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
            }
            foreach ($query->get() as $item) {
                if ($item->slug) {
                    $image = $item->image_path;
                    if (method_exists($item, 'getFirstMediaUrl')) {
                        $image = $item->getFirstMediaUrl($item instanceof Article ? 'article-image' : 'service-image') ?: $image;
                    }
                    $add(route($route, $item->slug), $item->updated_at, $image);
                }
            }
        }

        $limit = max(1, min(1000, (int) SeoHelper::getSetting('posts_per_sitemap', 500)));
        $chunks = array_chunk(array_values($entries), $limit);
        if ($part !== null) {
            abort_unless(ctype_digit($part) && (string) (int) $part === $part && isset($chunks[(int) $part - 1]), 404);
            $entries = $chunks[(int) $part - 1];
        } elseif (count($chunks) > 1) {
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');
            foreach ($chunks as $index => $chunk) {
                $xml->addChild('sitemap')->addChild('loc', htmlspecialchars(url('/sitemap-'.($index + 1).'.xml'), ENT_XML1));
            }

            return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
        }
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"></urlset>');
        $escape = fn ($value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        foreach ($entries as $entry) {
            $node = $xml->addChild('url');
            $node->addChild('loc', $escape($entry['url']));
            if ($entry['date']) {
                $node->addChild('lastmod', Carbon::parse($entry['date'])->toAtomString());
            }
            if ($entry['url'] === url('/')) {
                $priority = (string) SeoHelper::getSetting('homepage_priority', '1.0');
                $node->addChild('priority', in_array($priority, ['1.0', '0.8', '0.5'], true) ? $priority : '1.0');
            }
            if ($entry['image']) {
                $image = $node->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                $image->addChild('image:loc', $escape($entry['image']), 'http://www.google.com/schemas/sitemap-image/1.1');
            }
            foreach ($entry['videos'] as $object) {
                $ns = 'http://www.google.com/schemas/sitemap-video/1.1';
                $video = $node->addChild('video:video', null, $ns);
                foreach (['thumbnail_loc' => $object['thumbnailUrl'], 'title' => $object['name'], 'description' => strip_tags($object['description']), 'publication_date' => $object['uploadDate']] as $key => $value) {
                    $video->addChild('video:'.$key, $escape($value), $ns);
                }
                $key = isset($object['embedUrl']) ? 'player_loc' : 'content_loc';
                $video->addChild('video:'.$key, $escape($object['embedUrl'] ?? $object['contentUrl']), $ns);
            }
        }

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }
}
