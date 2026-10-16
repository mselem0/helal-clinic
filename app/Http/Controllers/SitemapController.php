<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SitePage;
use App\Services\SiteView;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        abort_unless(in_array(SeoHelper::getSetting('sitemap_enabled'), ['1', 1, true, 'true'], true), 404);
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        $add = function ($url, $date) use ($xml) {
            $node = $xml->addChild('url');
            $node->addChild('loc', htmlspecialchars($url, ENT_XML1));
            if ($date) {
                $node->addChild('lastmod', Carbon::parse($date)->toAtomString());
            }
        };
        foreach (SitePage::where('is_published', true)->get() as $page) {
            $seo = SeoPage::where('page_key', $page->page_key)->first();
            $url = SiteView::pageUrl($page);
            if (str_contains($seo?->robots ?: $page->robots, 'noindex')) {
                continue;
            }
            $canonical = $seo?->canonical_url ?: $page->canonical_url;
            if ($canonical && rtrim($canonical, '/') !== rtrim($url, '/')) {
                continue;
            }
            $add($url, $page->updated_at);
        }
        foreach ([Service::class => 'services.show', Article::class => 'blog.show', ClinicBranch::class => 'branches.show'] as $class => $route) {
            $query = $class::where($class === Article::class ? 'is_published' : 'is_active', true);
            if ($class === Article::class) {
                $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
            }
            foreach ($query->get() as $item) {
                if ($item->slug) {
                    $add(route($route, $item->slug), $item->updated_at);
                }
            }
        }

        return response($xml->asXML(), 200, ['Content-Type' => 'application/xml']);
    }
}
