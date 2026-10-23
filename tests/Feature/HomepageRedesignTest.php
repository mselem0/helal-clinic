<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\PageSection;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HomepageRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_empty_content_collections_are_hidden_and_only_published_articles_are_shown(): void
    {
        Article::query()->update(['is_published' => false]);
        \App\Models\Video::query()->update(['is_active' => false]);
        \App\Models\Testimonial::query()->update(['is_active' => false]);

        $this->get('/')->assertOk()
            ->assertDontSee('data-section="articles"', false)
            ->assertDontSee('data-section="videos"', false)
            ->assertDontSee('data-section="reviews"', false);

        $article = Article::create([
            'title' => 'مقال قيد المراجعة', 'slug' => 'homepage-pending-article',
            'content' => 'محتوى المقال', 'is_published' => false,
        ]);
        $this->get('/')->assertDontSee($article->title);

        $article->update(['is_published' => true, 'published_at' => now()->subMinute()]);
        $this->get('/')->assertSee($article->title)->assertSee('data-section="articles"', false);
    }

    public function test_home_faq_and_booking_sections_follow_linked_page_publication(): void
    {
        $faq = SitePage::where('page_key', 'faq')->firstOrFail();
        $appointment = SitePage::where('page_key', 'appointment')->firstOrFail();
        $faq->update(['is_published' => false]);
        $appointment->update(['is_published' => false]);

        $this->get('/')->assertOk()
            ->assertDontSee('data-section="faq"', false)
            ->assertDontSee('id="booking"', false);

        $faq->update(['is_published' => true]);
        $appointment->update(['is_published' => true]);
        $this->get('/')->assertSee('data-section="faq"', false)->assertSee('id="booking"', false);
    }

    public function test_custom_home_faq_content_renders_as_text_without_executing_markup(): void
    {
        $section = PageSection::where('section_key', 'faq')->firstOrFail();
        $section->update(['body' => json_encode([
            'fields' => [
                ['key' => 'field_1', 'value' => 'أسئلة مخصصة'],
                ['key' => 'field_2', 'value' => 'عنوان الأسئلة المخصصة'],
            ],
            'faqs' => [[
                'question' => '<script>alert("question")</script>',
                'answer' => '<img src=x onerror=alert("answer")>',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        $this->get('/')->assertOk()
            ->assertSee('عنوان الأسئلة المخصصة')
            ->assertSee('&lt;script&gt;alert(&quot;question&quot;)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(&quot;answer&quot;)&gt;', false)
            ->assertDontSee('<script>alert("question")</script>', false)
            ->assertDontSee('<img src=x onerror=alert("answer")>', false);
    }

    public function test_signed_home_preview_uses_linked_page_publication_drafts_without_changing_public_home(): void
    {
        $faq = SitePage::where('page_key', 'faq')->firstOrFail();
        $appointment = SitePage::where('page_key', 'appointment')->firstOrFail();
        $faq->update(['draft_data' => ['is_published' => false]]);
        $appointment->update(['draft_data' => ['is_published' => false]]);

        $this->get('/')->assertOk()->assertSee('data-section="faq"', false)->assertSee('id="booking"', false);

        $previewUrl = URL::temporarySignedRoute('home', now()->addMinutes(5), ['preview' => 1]);
        $this->get($previewUrl)->assertOk()
            ->assertDontSee('data-section="faq"', false)
            ->assertDontSee('id="booking"', false);

        $faq->update(['draft_data' => ['is_published' => true]]);
        $appointment->update(['draft_data' => ['is_published' => true]]);
        $faq->update(['is_published' => false]);
        $appointment->update(['is_published' => false]);

        $this->get('/')->assertDontSee('data-section="faq"', false)->assertDontSee('id="booking"', false);
        $this->get($previewUrl)->assertOk()->assertSee('data-section="faq"', false)->assertSee('id="booking"', false);
    }
}
