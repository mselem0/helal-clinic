<?php

namespace Tests\Feature;

use App\Helpers\SeoHelper;
use App\Livewire\Seo\SettingsTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class SitemapSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed();
    }

    public function test_sitemap_settings_remain_editable_but_cannot_publish_routes(): void
    {
        $settings = [
            'sitemap_enabled' => true,
            'include_homepage' => true,
            'include_services' => true,
            'include_branches' => true,
            'include_articles' => true,
            'include_pages' => true,
            'include_images_sitemap' => true,
            'include_videos_sitemap' => true,
            'posts_per_sitemap' => 2,
            'homepage_priority' => '0.8',
        ];

        Livewire::test(SettingsTab::class)
            ->fillForm($settings)
            ->call('save')
            ->assertHasNoFormErrors();

        foreach ($settings as $key => $value) {
            $this->assertSame((string) (is_bool($value) ? (int) $value : $value), (string) SeoHelper::getSetting($key));
        }

        $this->assertSitemapRoutesRemainUnavailable();
    }

    public function test_every_sitemap_switch_and_chunk_setting_keeps_routes_unavailable(): void
    {
        $keys = [
            'sitemap_enabled', 'include_homepage', 'include_services', 'include_branches',
            'include_articles', 'include_pages', 'include_images_sitemap', 'include_videos_sitemap',
            'posts_per_sitemap', 'homepage_priority',
        ];

        foreach ($keys as $key) {
            SeoHelper::setSetting($key, $key === 'posts_per_sitemap' ? '1' : 'true', 'sitemap');
            $this->assertSitemapRoutesRemainUnavailable();
        }

        foreach ($keys as $key) {
            SeoHelper::setSetting($key, $key === 'posts_per_sitemap' ? '1000' : '1', 'sitemap');
        }
        $this->assertSitemapRoutesRemainUnavailable();
    }

    private function assertSitemapRoutesRemainUnavailable(): void
    {
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/sitemap-1.xml')->assertNotFound();
    }
}
