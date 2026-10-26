<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SitePage;
use App\Models\Video;
use App\Support\AdsContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdsContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_all_seeded_public_pages_and_details_exclude_topics_without_writing_cms_data(): void
    {
        Article::where('slug', 'article-2')->update(['is_published' => true]);
        $original = PageSection::orderBy('id')->get()->toArray();
        $profile = SitePage::where('page_key', 'about')->first()->doctor_profile;
        $paths = SitePage::where('is_published', true)->get()->map(fn ($page) => \App\Services\SiteView::pageUrl($page))->all();
        foreach (Service::where('is_active', true)->get() as $service) {
            if (AdsContent::allowed($service)) {
                $paths[] = $service->publicPath();
            }
        }
        foreach (Article::where('is_published', true)->get() as $article) {
            if (AdsContent::allowed($article)) {
                $paths[] = $article->publicPath();
            }
        }
        foreach ($paths as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertFalse(AdsContent::contains($html), 'Excluded topic in '.$path);
            $this->assertStringNotContainsString('/services/service-2', $html);
            $this->assertStringNotContainsString('/blog/article-2', $html);
        }
        $this->assertSame($original, PageSection::orderBy('id')->get()->toArray());
        $this->assertSame($profile, SitePage::where('page_key', 'about')->first()->doctor_profile);
        $this->assertTrue(Service::where('slug', 'service-2')->first()->is_active);
        $this->get('/services/service-2')->assertNotFound();
        $this->get('/blog/article-2')->assertNotFound();
    }

    public function test_new_topic_records_and_cms_preview_cannot_reintroduce_excluded_content(): void
    {
        $service = Service::create(['name' => 'علاج تأخر الانجاب', 'slug' => 'new-treatment', 'description' => 'وصف', 'is_active' => true]);
        Article::create(['title' => 'ICSI overview', 'slug' => 'new-article', 'is_published' => true]);
        SitePage::create(['page_key' => 'custom-topic', 'title' => 'علاج العقم', 'slug' => 'new-page', 'is_published' => true]);
        Video::create(['title' => 'الحقن المجهري', 'reel' => '555', 'is_active' => true]);
        $this->get('/services/new-treatment')->assertNotFound();
        $this->get('/blog/new-article')->assertNotFound();
        $this->get('/new-page')->assertNotFound();
        $this->get(SitePage::where('page_key', 'videos')->first()->url)->assertOk()->assertDontSee('data-reel="555"', false);
        $this->postJson('/bookings', ['patient_name' => 'اختبار', 'patient_phone' => '01012345678', 'booking_date' => now()->addWeek()->format('Y-m-d'), 'service_id' => $service->id, 'clinic_branch_id' => \App\Models\ClinicBranch::first()->id])->assertNotFound();
        $page = SitePage::where('page_key', 'about')->first();
        $page->update(['draft_data' => ['intro' => 'استشاري الحقن المجهري']]);
        $response = $this->get(URL::temporarySignedRoute('site.preview', now()->addMinutes(5), ['page' => $page->id, 'preview' => 1]))->assertOk();
        $this->assertFalse(AdsContent::contains($response->getContent()));
    }

    public function test_original_hero_is_pinned_even_with_copied_database_or_draft_video_source(): void
    {
        Video::where('reel', AdsContent::HERO_REEL)->update(['external_video_url' => 'https://example.test/unreviewed-video.mp4']);
        $response = $this->get('/')->assertOk()->assertSee('data-title="'.AdsContent::HERO_TITLE.'"', false)
            ->assertSee('/reel-'.AdsContent::HERO_REEL.'.mp4', false);
        preg_match('/class="feature-preview"(.*?)>/s', $response->getContent(), $hero);
        $this->assertStringNotContainsString('unreviewed-video.mp4', $hero[1]);
        $this->assertFalse(AdsContent::contains($response->getContent()));
    }
}
