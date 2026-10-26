<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\SeoPage;
use App\Models\SiteNavigation;
use App\Models\SitePage;
use App\Models\ThemeRevision;
use App\Services\ThemeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicPageTimestampTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_discard_publish_and_restore_have_reliable_public_timestamps(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $page = SitePage::where('page_key', 'about')->firstOrFail();
        $initial = $page->public_updated_at->toIso8601String();
        $drafts = app(ThemeDraftService::class);
        $this->travel(1)->days();
        $page->update(['draft_data' => ['title' => 'مسودة جديدة']]);
        $this->assertSame($initial, $page->fresh()->public_updated_at->toIso8601String());
        $drafts->discardDrafts();
        $this->assertSame($initial, $page->fresh()->public_updated_at->toIso8601String());
        $page->refresh()->update(['draft_data' => ['title' => 'عنوان منشور جديد']]);
        $drafts->publish();
        $published = $page->fresh()->public_updated_at;
        $this->assertTrue($published->equalTo(now()->startOfSecond()));
        $this->assertGreaterThan($initial, $published->toIso8601String());
        $revision = ThemeRevision::latest('id')->firstOrFail()->id;
        $this->travel(1)->days();
        $drafts->restoreRevision($revision);
        $this->assertTrue($page->fresh()->public_updated_at->equalTo(now()->startOfSecond()));
        $restored = $page->fresh()->public_updated_at->toIso8601String();
        $this->travel(1)->days();
        $drafts->restoreRevision($revision);
        $this->assertSame($restored, $page->fresh()->public_updated_at->toIso8601String());
        $this->travelBack();
    }

    public function test_legacy_seo_drafts_do_not_change_public_timestamp_but_publishing_them_does(): void
    {
        $this->seed();
        $seo = SeoPage::create(['page_key' => 'about', 'page_label' => 'عن الطبيب', 'meta_title' => 'عنوان منشور']);
        $before = $seo->public_updated_at->toIso8601String();
        $this->get('/sitemap.xml')->assertNotFound();
        $this->travel(1)->days();
        $seo->update(['draft_data' => ['meta_title' => 'عنوان بحث منشور جديد']]);
        $this->assertSame($before, $seo->fresh()->public_updated_at->toIso8601String());
        app(ThemeDraftService::class)->publish();
        $this->assertTrue($seo->fresh()->public_updated_at->equalTo(now()->startOfSecond()));
        $this->get('/sitemap.xml')->assertNotFound();
        $this->travelBack();
    }

    public function test_shared_section_and_menu_drafts_do_not_change_public_dates_until_publish(): void
    {
        $this->seed();
        $section = PageSection::where('section_key', 'header')->firstOrFail();
        $beforeSection = $section->public_updated_at->toIso8601String();
        $menu = SiteNavigation::firstOrFail();
        $beforeMenu = $menu->public_updated_at->toIso8601String();
        $this->get('/sitemap.xml')->assertNotFound();
        $this->travel(1)->days();
        $section->update(['draft_data' => ['title' => 'عنوان رأس جديد']]);
        $items = $menu->items;
        $items[0]['label'] = 'قائمة جديدة';
        $menu->update(['draft_data' => ['items' => $items]]);
        $this->assertSame($beforeSection, $section->fresh()->public_updated_at->toIso8601String());
        $this->assertSame($beforeMenu, $menu->fresh()->public_updated_at->toIso8601String());
        app(ThemeDraftService::class)->publish();
        $this->assertTrue($section->fresh()->public_updated_at->equalTo(now()->startOfSecond()));
        $this->assertTrue($menu->fresh()->public_updated_at->equalTo(now()->startOfSecond()));
        $this->get('/sitemap.xml')->assertNotFound();
        $this->travelBack();
    }
}
