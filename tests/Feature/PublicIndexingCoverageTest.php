<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\SitePage;
use App\Services\SiteView;
use Database\Seeders\ReviewedArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicIndexingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_published_public_page_is_in_sitemap_and_has_consistent_indexing_signals(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);
        SitePage::create(['page_key' => 'custom', 'slug' => 'extra-public-page', 'title' => 'صفحة عامة إضافية', 'body' => '<p>محتوى منشور</p>', 'is_published' => true, 'robots' => 'index_follow']);
        $urls = SitePage::where('is_published', true)->get()->map(fn ($page) => SiteView::pageUrl($page));
        foreach ([Service::where('is_active', true)->get(), ClinicBranch::where('is_active', true)->get(), Article::where('is_published', true)->get()] as $items) {
            $urls = $urls->merge($items->map(fn ($item) => url($item->publicPath())));
        }
        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $listed = array_map('strval', $xml->xpath('//*[local-name()="url"]/*[local-name()="loc"]'));
        $this->assertEqualsCanonicalizing($urls->all(), $listed);
        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
        $this->assertStringNotContainsString("Disallow: /\n", $robots);
        foreach ($urls as $url) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();
            $this->assertStringNotContainsString('noindex', $response->headers->get('X-Robots-Tag'));
            $this->assertStringContainsString('<meta name="robots" content="index,follow', $html);
            $this->assertSame(1, substr_count($html, 'rel="canonical"'));
            $this->assertStringContainsString('rel="canonical" href="'.e($url).'"', $html);
            $this->assertMatchesRegularExpression('/<title>[^<]+<\/title>/u', $html);
        }
        foreach (['/codeAiAdminDash', '/site-preview/', '/patient/'] as $private) {
            $this->assertStringContainsString('Disallow: '.$private, $robots);
            $this->assertStringNotContainsString($private, implode("\n", $listed));
        }
    }
}
