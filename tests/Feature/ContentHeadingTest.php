<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentHeadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_promoted_content_pages_keep_one_h1_and_hide_the_redundant_masthead_heading(): void
    {
        $pages = [
            ['appointment', '/appointment', 'خلّينا نرتّب'],
            ['about', '/about-us', 'د. محمد هلال'],
            ['journey', '/patient-journey', 'كل رحلة مختلفة.'],
            ['videos', '/gallery', 'دقيقة مع د. هلال'],
            ['faq', '/frequently-asked-questions', 'أسئلة شائعة'],
            ['contact', '/contact-us', 'تواصلي مع فريق العيادة'],
        ];

        foreach ($pages as [$pageKey, $path, $heading]) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, $this->h1Count($html), "{$pageKey} should render exactly one H1.");
            $this->assertStringContainsString('<h1', $html, "{$pageKey} should retain a semantic H1.");
            $this->assertStringContainsString($heading, $html, "{$pageKey} should promote its main content heading.");
            $this->assertStringNotContainsString('<h1>'.SitePage::where('page_key', $pageKey)->value('title').'</h1>', $html, "{$pageKey} should not repeat its masthead title as another H1.");
        }
    }

    public function test_custom_page_keeps_its_visible_masthead_h1(): void
    {
        SitePage::create([
            'page_key' => 'custom-information',
            'slug' => 'custom-information',
            'title' => 'معلومات مخصصة',
            'body' => '<p>محتوى الصفحة</p>',
            'sections' => [],
            'is_published' => true,
            'robots' => 'index_follow',
        ]);

        $html = $this->get('/custom-information')->assertOk()->getContent();

        $this->assertSame(1, $this->h1Count($html));
        $this->assertStringContainsString('<h1>معلومات مخصصة</h1>', $html);
        $this->assertStringContainsString('محتوى الصفحة', $html);
    }

    public function test_missing_or_disabled_heading_section_falls_back_to_the_masthead_h1(): void
    {
        $page = SitePage::where('page_key', 'appointment')->firstOrFail();
        $section = PageSection::where('section_key', 'booking')->firstOrFail();

        $section->update(['is_active' => false]);
        $html = $this->get('/appointment')->assertOk()->getContent();
        $this->assertSame(1, $this->h1Count($html));
        $this->assertStringContainsString('<h1>طلب موعد</h1>', $html);

        $section->update(['is_active' => true]);
        $page->update(['sections' => []]);
        $html = $this->get('/appointment')->assertOk()->getContent();
        $this->assertSame(1, $this->h1Count($html));
        $this->assertStringContainsString('<h1>طلب موعد</h1>', $html);
    }

    public function test_promoted_heading_stays_the_only_h1_when_cms_reorders_sections(): void
    {
        SitePage::where('page_key', 'about')->firstOrFail()->update([
            'sections' => [['key' => 'services'], ['key' => 'about']],
        ]);

        $html = $this->get('/about-us')->assertOk()->getContent();

        $this->assertSame(1, $this->h1Count($html));
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('د. محمد هلال', $html);
        $this->assertStringContainsString('كل مرحلة لها اهتمامها.', $html);
    }

    public function test_home_service_and_article_pages_keep_their_existing_main_h1(): void
    {
        $service = Service::firstOrFail();
        $article = Article::create([
            'title' => 'عنوان المقال',
            'slug' => 'heading-test-article',
            'content' => '<p>محتوى المقال</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        foreach ([
            ['/', 'د. محمد هلال'],
            ['/services', 'خدمات العيادة'],
            ['/services/'.$service->slug, $service->name],
            ['/blog/'.$article->slug, $article->title],
        ] as [$path, $heading]) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertSame(1, $this->h1Count($html), "{$path} should render exactly one H1.");
            $this->assertStringContainsString('<h1', $html);
            $this->assertStringContainsString($heading, $html);
        }
    }

    private function h1Count(string $html): int
    {
        return preg_match_all('/<h1(?:\s[^>]*)?>/i', $html);
    }
}
