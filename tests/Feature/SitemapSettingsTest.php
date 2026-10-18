<?php

namespace Tests\Feature;

use App\Helpers\SeoHelper;
use App\Livewire\Seo\SettingsTab;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SitemapSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::preventStrayRequests();
        $this->seed();
        Article::create(['title' => 'مقال منشور', 'slug' => 'sitemap-article', 'is_published' => true, 'published_at' => now()->subDay()]);
    }

    private function locations(): array
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);

        return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
    }

    public function test_missing_settings_include_all_categories_and_form_defaults_match(): void
    {
        $locations = $this->locations();
        foreach (['/', '/services', '/blog', '/branches', '/about-us', '/blog/sitemap-article'] as $path) {
            $this->assertContains(url($path), $locations);
        }
        $component = Livewire::test(SettingsTab::class);
        foreach (['include_homepage', 'include_services', 'include_branches', 'include_articles', 'include_pages'] as $key) {
            $component->assertSet('data.'.$key, true);
        }
    }

    public function test_each_category_switch_excludes_only_its_own_urls_and_can_be_reenabled(): void
    {
        $groups = [
            'include_homepage' => [url('/')],
            'include_services' => [url('/services'), url('/services/'.Service::first()->slug)],
            'include_articles' => [url('/blog'), url('/blog/sitemap-article')],
            'include_branches' => [url('/branches'), url('/branches/'.ClinicBranch::first()->slug)],
            'include_pages' => [url('/about-us'), url('/contact-us')],
        ];
        foreach ($groups as $key => $excluded) {
            SeoHelper::setSetting($key, '0', 'sitemap');
            $locations = $this->locations();
            foreach ($excluded as $url) {
                $this->assertNotContains($url, $locations);
            }
            foreach ($groups as $other => $urls) {
                if ($other !== $key) {
                    foreach ($urls as $url) {
                        $this->assertContains($url, $locations);
                    }
                }
            }
            SeoHelper::setSetting($key, 'true', 'sitemap');
            foreach ($excluded as $url) {
                $this->assertContains($url, $this->locations());
            }
        }
    }

    public function test_string_false_and_global_disable_are_respected(): void
    {
        SeoHelper::setSetting('include_homepage', 'false', 'sitemap');
        $this->assertNotContains(url('/'), $this->locations());
        SeoHelper::setSetting('sitemap_enabled', '0', 'sitemap');
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_detail_categories_with_effective_legacy_noindex_are_excluded(): void
    {
        SeoPage::create(['page_key' => 'service', 'page_label' => 'خدمة', 'robots' => 'noindex_follow']);
        $locations = $this->locations();
        $this->assertNotContains(url('/services/'.Service::first()->slug), $locations);
        $this->assertContains(url('/services'), $locations);
        $this->assertContains(url('/blog/sitemap-article'), $locations);
    }
}
