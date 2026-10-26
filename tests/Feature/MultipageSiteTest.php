<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteNavigationResource\Pages\EditSiteNavigation;
use App\Filament\Resources\SitePageResource\Pages\CreateSitePage;
use App\Filament\Resources\SitePageResource\Pages\EditSitePage;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\SeoRedirect;
use App\Models\Service;
use App\Models\SiteNavigation;
use App\Models\SitePage;
use App\Models\ThemeRevision;
use App\Models\User;
use App\Services\ThemeDraftService;
use Database\Seeders\MultipageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MultipageSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
    }

    public function test_all_pages_are_real_documents_with_unique_metadata_in_both_themes(): void
    {
        foreach (['helal_classic', 'helal_modern'] as $theme) {
            foreach (SitePage::all() as $page) {
                $url = URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1, 'theme' => $theme]);
                $html = $this->get($url)->assertOk()->assertSee($theme)->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex')->getContent();
                $this->assertSame(1, substr_count($html, '<title>'));
                $this->assertSame(1, substr_count($html, 'rel="canonical"'));
                $this->assertSame(1, preg_match_all('/<h1(?:\s[^>]*)?>/', $html));
            }
        }
        foreach (['/about-us', '/contact-us', '/gallery'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('http-equiv="refresh"', false);
        }
    }

    public function test_page_draft_preview_publish_discard_restore_and_navigation_are_atomic(): void
    {
        $page = SitePage::where('page_key', 'about')->first();
        $old = $page->title;
        $menu = SiteNavigation::where('location', 'header')->first();
        $page->update(['draft_data' => ['title' => 'عنوان خاص للمراجعة', 'meta_title' => 'بحث خاص للمراجعة']]);
        $menu->update(['draft_data' => ['items' => [['page_key' => 'about', 'label' => 'قائمة خاصة للمراجعة']]]]);
        $this->get('/about-us')->assertDontSee('عنوان خاص للمراجعة')->assertDontSee('قائمة خاصة للمراجعة');
        $preview = URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1]);
        $this->get($preview)->assertSee('عنوان خاص للمراجعة')->assertSee('بحث خاص للمراجعة')->assertSee('قائمة خاصة للمراجعة');
        $drafts = app(ThemeDraftService::class);
        $drafts->publish();
        $revision = ThemeRevision::first()->id;
        $this->get('/about-us')->assertSee('عنوان خاص للمراجعة')->assertSee('قائمة خاصة للمراجعة');
        $page->refresh()->update(['draft_data' => ['title' => 'مسودة ملغاة']]);
        $drafts->discardDrafts();
        $this->assertNull($page->fresh()->draft_data);
        $drafts->restoreRevision($revision);
        $this->get('/about-us')->assertSee($old)->assertDontSee('عنوان خاص للمراجعة')->assertDontSee('قائمة خاصة للمراجعة');
        $this->assertIsArray(SiteNavigation::first()->fresh()->items);
    }

    public function test_page_creation_and_editing_from_filament_only_save_drafts(): void
    {
        $this->admin();
        Livewire::test(CreateSitePage::class)->fillForm(['title' => 'صفحة مخصصة', 'slug' => 'custom-information', 'body' => 'معلومات للمراجعة', 'is_published' => true, 'sections' => [], 'robots' => 'index_follow'])->call('create')->assertHasNoFormErrors();
        $page = SitePage::where('slug', 'custom-information')->firstOrFail();
        $this->get('/custom-information')->assertNotFound();
        $this->get(URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1]))->assertOk()->assertSee('معلومات للمراجعة');
        app(ThemeDraftService::class)->publish();
        Livewire::test(EditSitePage::class, ['record' => $page->id])->fillForm(['title' => 'عنوان معدل'])->call('save')->assertHasNoFormErrors();
        $this->get('/custom-information')->assertSee('صفحة مخصصة')->assertDontSee('عنوان معدل');
    }

    public function test_menu_editor_preserves_public_navigation_until_publish(): void
    {
        $this->admin();
        $menu = SiteNavigation::where('location', 'footer')->first();
        Livewire::test(EditSiteNavigation::class, ['record' => $menu->id])->fillForm(['items' => [['label' => 'رابط مخصص', 'page_key' => 'about']]])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertDontSee('رابط مخصص');
        app(ThemeDraftService::class)->publish();
        $this->get('/')->assertSee('رابط مخصص');
    }

    public function test_fertility_urls_and_future_articles_are_not_public_and_sitemap_stays_unavailable(): void
    {
        SitePage::create(['page_key' => 'private', 'slug' => 'private-content', 'title' => 'خاص', 'is_published' => false]);
        Article::create(['title' => 'مقال مستقبلي', 'slug' => 'future-article', 'is_published' => true, 'published_at' => now()->addDays(3)]);
        $this->get('/services/service-2')->assertNotFound();
        $this->get('/blog/article-2')->assertNotFound();
        $this->get('/blog/future-article')->assertNotFound();
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/sitemap-1.xml')->assertNotFound();
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /site-preview/')->assertDontSee('Sitemap:');
    }

    public function test_slug_changes_redirect_and_reversing_them_does_not_loop(): void
    {
        $page = SitePage::where('page_key', 'about')->first();
        $page->update(['draft_data' => ['slug' => 'meet-the-doctor']]);
        $this->get('/about-us')->assertOk();
        app(ThemeDraftService::class)->publish();
        $this->get('/about-us')->assertStatus(301)->assertRedirect('/meet-the-doctor');
        $this->get('/meet-the-doctor')->assertOk();
        $page->refresh()->update(['slug' => 'about-us']);
        $this->get('/meet-the-doctor')->assertStatus(301)->assertRedirect('/about-us');
        $this->get('/about-us')->assertOk();
        $service = Service::first();
        $old = $service->slug;
        $service->update(['slug' => 'updated-service']);
        $this->get('/services/'.$old)->assertRedirect('/services/updated-service');
    }

    public function test_redirect_loop_and_reserved_page_slug_are_rejected(): void
    {
        SeoRedirect::create(['source_url' => '/a', 'target_url' => '/b', 'redirect_type' => 301, 'is_active' => true]);
        try {
            SeoRedirect::create(['source_url' => '/b', 'target_url' => '/a', 'redirect_type' => 301, 'is_active' => true]);
            $this->fail('loop allowed');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('target_url', $e->errors());
        }
        $this->expectException(ValidationException::class);
        ThemeDraftService::validatePageDraft(SitePage::first(), ['slug' => 'services']);
    }

    public function test_medical_authorship_schema_related_content_and_article_pagination(): void
    {
        $service = Service::first();
        $branch = ClinicBranch::first();
        $branch->update(['service_ids' => [$service->id], 'phone' => '01012345678']);
        for ($i = 1; $i <= 13; $i++) {
            Article::create(['title' => 'مقال موثق '.$i, 'slug' => 'reviewed-'.$i, 'content' => 'معلومات مراجعة', 'is_published' => true, 'published_at' => now()->subDays($i), 'author_name' => 'كاتب فعلي', 'medical_reviewer' => 'مراجع فعلي', 'reviewed_at' => now()->subDay(), 'sources' => [['title' => 'مصدر طبي', 'url' => 'https://example.org/reference']], 'service_ids' => [$service->id]]);
        }
        $html = $this->get('/blog/reviewed-1')->assertSee('مراجع فعلي')->assertSee('مصدر طبي')->assertSee('datePublished')->assertSee('BreadcrumbList')->getContent();
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
        $this->assertNotNull(json_decode($m[1], true));
        $this->get('/blog?page=2')->assertOk()->assertSee('?page=2', false)->assertSee('مقال موثق 13')->assertDontSee('مقال موثق 1</', false);
        $this->get('/services/'.$service->slug)->assertSee($branch->name)->assertSee('مقال موثق');
        $this->get('/branches/'.$branch->slug)->assertSee($service->name)->assertSee('MedicalClinic');
    }

    public function test_seo_editor_drafts_are_private_until_publish_and_restore(): void
    {
        $seo = SeoPage::where('page_key', 'home')->first();
        $old = $seo->meta_title;
        preg_match('~<title>(.*?)</title>~', $this->get('/')->assertOk()->getContent(), $originalTitle);
        $seo->update(['draft_data' => ['meta_title' => 'عنوان بحث مسودة']]);
        $this->get('/')->assertDontSee('عنوان بحث مسودة');
        $this->get(URL::temporarySignedRoute('home', now()->addMinutes(10), ['preview' => 1]))->assertSee('عنوان بحث مسودة');
        $drafts = app(ThemeDraftService::class);
        $drafts->publish();
        $this->get('/')->assertSee('عنوان بحث مسودة');
        $drafts->restoreRevision(ThemeRevision::first()->id);
        $restored = $this->get('/')->assertDontSee('عنوان بحث مسودة')->getContent();
        preg_match('~<title>(.*?)</title>~', $restored, $restoredTitle);
        $this->assertSame($old, $seo->fresh()->meta_title);
        $this->assertSame($originalTitle[1] ?? null, $restoredTitle[1] ?? null);
    }

    public function test_admin_permissions_and_404_are_enforced(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $this->get('/codeAiAdminDash/site-pages')->assertForbidden();
        $this->get('/codeAiAdminDash/site-navigations/1/edit')->assertForbidden();
        $this->get('/missing-content')->assertNotFound()->assertSee('الصفحة غير موجودة');
        $this->get('/site-preview/1?preview=1')->assertForbidden();
    }

    public function test_arabic_slug_redirects_match_encoded_requests_and_absolute_self_redirect_is_rejected(): void
    {
        $service = Service::first();
        $service->update(['slug' => 'متابعة-الحمل']);
        $service->update(['slug' => 'رعاية-الحمل']);
        $this->get('/services/'.rawurlencode('متابعة-الحمل'))->assertStatus(301)->assertRedirect('/services/رعاية-الحمل');
        $this->expectException(ValidationException::class);
        SeoRedirect::create(['source_url' => '/loop', 'target_url' => url('/loop'), 'redirect_type' => 301, 'is_active' => true]);
    }

    public function test_invalid_and_out_of_range_article_pages_return_real_404(): void
    {
        foreach (['/blog?page=0', '/blog?page=abc', '/blog?page=999'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_homepage_section_order_is_a_page_draft(): void
    {
        $page = SitePage::where('page_key', 'home')->first();
        $page->update(['draft_data' => ['sections' => [['key' => 'services'], ['key' => 'about']]]]);
        $this->get('/')->assertSee('id="videos"', false);
        $preview = URL::temporarySignedRoute('home', now()->addMinutes(5), ['preview' => 1]);
        $html = $this->get($preview)->assertOk()->assertDontSee('id="videos"', false)->getContent();
        $this->assertLessThan(strpos($html, 'id="about"'), strpos($html, 'id="services"'));
        app(ThemeDraftService::class)->publish();
        $this->get('/')->assertDontSee('id="videos"', false);
    }

    public function test_upgrade_seed_is_idempotent_and_preserves_content(): void
    {
        $page = SitePage::where('page_key', 'about')->first();
        $page->update(['title' => 'عنوان محفوظ']);
        $service = Service::first();
        $service->update(['description' => 'بيانات محفوظة']);
        $this->seed(MultipageSeeder::class);
        $this->seed(MultipageSeeder::class);
        $this->assertSame('عنوان محفوظ', $page->fresh()->title);
        $this->assertSame('بيانات محفوظة', $service->fresh()->description);
        $this->assertDatabaseCount('site_pages', 10);
        $this->assertDatabaseCount('site_navigations', 2);
    }
}
