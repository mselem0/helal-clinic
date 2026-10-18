<?php

namespace Tests\Feature;

use App\Helpers\SeoHelper;
use App\Livewire\Seo\SettingsTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class RobotsSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed();
    }

    public function test_editor_saves_rules_to_database_without_creating_a_static_file(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $rules = "# قواعد اختبار\nDisallow: /private/\nAllow: /private/public/";
        Livewire::test(SettingsTab::class)->fillForm(['robots_extra' => $rules])->call('save')->assertHasNoFormErrors();
        $this->assertSame($rules, SeoHelper::getSetting('robots_extra'));
        Livewire::test(SettingsTab::class)->assertSet('data.robots_extra', $rules);
        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee($rules, false)->assertSee('Disallow: /site-preview/')->assertSee('Disallow: /codeAiAdminDash')->assertSee('Disallow: /patient-auth/');
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_sitemap_declaration_tracks_enabled_state(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'), false);
        SeoHelper::setSetting('sitemap_enabled', '0');
        $this->get('/robots.txt')->assertOk()->assertDontSee('Sitemap:');
    }

    public function test_editor_rejects_new_user_agent_groups_and_html(): void
    {
        foreach (["User-agent: *\nDisallow: /", '<script>bad</script>', 'Sitemap: https://example.test/other.xml'] as $rules) {
            Livewire::test(SettingsTab::class)->fillForm(['robots_extra' => $rules])->call('save')->assertHasFormErrors(['robots_extra']);
        }
        $this->assertNull(SeoHelper::getSetting('robots_extra'));
    }
}
