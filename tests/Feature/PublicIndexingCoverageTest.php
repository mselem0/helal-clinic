<?php

namespace Tests\Feature;

use App\Models\Article;
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

    public function test_public_pages_are_never_indexable_and_fertility_content_is_not_publicly_reachable(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);
        SitePage::create(['page_key' => 'custom', 'slug' => 'extra-public-page', 'title' => 'صفحة عامة إضافية', 'body' => '<p>محتوى منشور</p>', 'is_published' => true, 'robots' => 'index_follow']);

        $urls = SitePage::where('is_published', true)->get()->map(fn ($page) => SiteView::pageUrl($page));
        foreach (['/', '/services', '/blog', '/branches', '/about-us', '/contact-us', '/extra-public-page'] as $path) {
            $urls->push(url($path));
        }

        foreach ($urls->unique() as $url) {
            $response = $this->get($url)->assertOk();
            $html = $response->getContent();
            $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
            $this->assertStringContainsString('<meta name="robots" content="noindex,nofollow', $html);
            $this->assertSame(1, substr_count($html, 'rel="canonical"'));
            $this->assertMatchesRegularExpression('/<title>[^<]+<\/title>/u', $html);
        }

        $this->get('/services/service-2')->assertNotFound();
        $this->get('/blog/article-2')->assertNotFound();
        $this->assertDatabaseHas('services', ['slug' => 'service-2', 'is_active' => true]);
        $this->assertDatabaseHas('articles', ['slug' => 'article-2', 'is_published' => true]);
        $this->assertStringNotContainsString('service-2', $this->get('/services')->getContent());
        $this->assertStringNotContainsString('article-2', $this->get('/blog')->getContent());
        $this->assertGreaterThan(0, Service::where('is_active', true)->count());
        $this->assertGreaterThan(0, Article::where('is_published', true)->count());

        $robots = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringNotContainsString('Sitemap:', $robots);
        $this->assertStringNotContainsString("Disallow: /\n", $robots);
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/sitemap-1.xml')->assertNotFound();
    }
}
