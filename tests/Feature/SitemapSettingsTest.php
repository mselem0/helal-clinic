<?php

namespace Tests\Feature;

use App\Helpers\SeoHelper;
use App\Livewire\Seo\SettingsTab;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\Video;
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

    public function test_sitemap_splits_into_valid_chunks_without_losing_or_duplicating_urls(): void
    {
        $expected = $this->locations();
        SeoHelper::setSetting('posts_per_sitemap', '2', 'sitemap');
        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $this->assertSame('sitemapindex', $index->getName());
        $links = array_map('strval', $index->xpath('//*[local-name()="loc"]'));
        $actual = [];
        foreach ($links as $link) {
            $xml = simplexml_load_string($this->get($link)->assertOk()->getContent());
            $locations = array_map('strval', $xml->xpath('//*[local-name()="url"]/*[local-name()="loc"]'));
            $this->assertLessThanOrEqual(2, count($locations));
            $actual = array_merge($actual, $locations);
        }
        $this->assertSame($expected, $actual);
        $this->assertSame($actual, array_values(array_unique($actual)));
        foreach (['/sitemap-0.xml', '/sitemap-999.xml', '/sitemap-01.xml'] as $path) {
            $this->get($path)->assertNotFound();
        }
        SeoHelper::setSetting('sitemap_enabled', '0', 'sitemap');
        $this->get('/sitemap-1.xml')->assertNotFound();
    }

    public function test_media_extensions_follow_switches_and_use_actual_player_urls(): void
    {
        Service::first()->update(['image_path' => 'landing/service&photo.jpg']);
        Video::create(['title' => 'سؤال & إجابة', 'description' => 'شرح <مبسط> & موثق', 'image_path' => 'landing/video.jpg', 'external_video_url' => 'https://vimeo.com/999888777?h=abc123', 'uploaded_at' => now()->subDay(), 'is_active' => true]);
        $this->get('/sitemap.xml')->assertDontSee('<image:image', false)->assertDontSee('<video:video', false);
        SeoHelper::setSetting('include_images_sitemap', '1', 'sitemap');
        SeoHelper::setSetting('include_videos_sitemap', '1', 'sitemap');
        $xml = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $xml->registerXPathNamespace('video', 'http://www.google.com/schemas/sitemap-video/1.1');
        $images = array_map('strval', $xml->xpath('//image:loc'));
        $this->assertContains(asset('storage/landing/service&photo.jpg'), $images);
        $this->assertSame(['https://player.vimeo.com/video/999888777?h=abc123'], array_map('strval', $xml->xpath('//video:player_loc')));
        $this->assertCount(0, $xml->xpath('//video:content_loc'));
        $this->assertSame('سؤال & إجابة', (string) $xml->xpath('//video:title')[0]);
        SeoHelper::setSetting('include_pages', '0', 'sitemap');
        $this->get('/sitemap.xml')->assertDontSee('<video:video', false);
    }

    public function test_draft_saves_do_not_advertise_false_lastmod_for_general_pages(): void
    {
        $page = SitePage::where('page_key', 'about')->firstOrFail();
        $before = $this->get('/sitemap.xml')->getContent();
        $this->travel(2)->days();
        $page->update(['draft_data' => ['title' => 'مسودة سرية']]);
        $this->assertSame($before, $this->get('/sitemap.xml')->getContent());
        $this->travelBack();
    }
}
