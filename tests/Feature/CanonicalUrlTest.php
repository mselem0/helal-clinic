<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\SeoPage;
use App\Models\SeoRedirect;
use App\Models\Service;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function canonical(string $path): string
    {
        $html = $this->get($path)->assertOk()->getContent();
        preg_match_all('~<link rel="canonical" href="([^"]+)">~', $html, $matches);
        $this->assertCount(1, $matches[1]);

        return html_entity_decode($matches[1][0], ENT_QUOTES, 'UTF-8');
    }

    public function test_unrelated_page_queries_do_not_create_duplicate_canonical_urls(): void
    {
        $path = '/services/'.Service::first()->slug;
        $this->assertSame(url($path), $this->canonical($path.'?page=2&utm_source=test'));
        $this->assertSame(url('/about-us'), $this->canonical('/about-us?page=3&utm_source=test'));
        $this->assertSame(url('/'), $this->canonical('/?page=4'));
    }

    public function test_article_pagination_merges_existing_query_without_duplicate_page_or_fragment(): void
    {
        for ($i = 0; $i < 13; $i++) {
            Article::create(['title' => 'مقال '.$i, 'slug' => 'canonical-'.$i, 'is_published' => true]);
        }
        SeoPage::where('page_key', 'articles')->delete();
        SitePage::where('page_key', 'articles')->update(['canonical_url' => url('/blog').'?source=clinic&page=1#section']);
        $this->assertSame(url('/blog').'?source=clinic&page=2', $this->canonical('/blog?page=2'));
    }

    public function test_default_paginated_canonical_stays_separate_from_first_page(): void
    {
        for ($i = 0; $i < 13; $i++) {
            Article::create(['title' => 'مقال '.$i, 'slug' => 'pagination-'.$i, 'is_published' => true]);
        }
        $this->assertSame(url('/blog'), $this->canonical('/blog?utm_source=test'));
        $this->assertSame(url('/blog').'?page=2', $this->canonical('/blog?page=2&utm_source=test'));
    }

    public function test_gone_pages_return_410_without_a_redirect_location(): void
    {
        SeoRedirect::create(['source_url' => '/retired-content', 'target_url' => '/about-us', 'redirect_type' => 410, 'match_type' => 'exact', 'is_active' => true]);
        $this->get('/retired-content')->assertStatus(410)->assertHeaderMissing('Location')->assertHeader('X-Robots-Tag', 'noindex');
        $this->head('/retired-content')->assertStatus(410)->assertHeaderMissing('Location');
    }
}
