<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServicePageDesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function service(array $attributes = []): Service
    {
        static $next = 0;
        $next++;

        return Service::create(array_merge([
            'name' => 'خدمة اختبار '.$next,
            'slug' => 'service-test-'.$next,
            'description' => 'وصف مختصر للخدمة '.$next,
            'is_active' => true,
            'order' => $next,
        ], $attributes));
    }

    public function test_service_page_uses_one_service_heading_and_shows_plain_description_without_rich_content(): void
    {
        $service = $this->service(['rich_description' => null]);
        $html = $this->get('/services/'.$service->slug)->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<h1(?:\s[^>]*)?>/i', $html));
        $this->assertStringContainsString($service->name, $html);
        $this->assertStringContainsString($service->description, $html);
        $this->assertStringNotContainsString('src=""', $html);
    }

    public function test_service_rich_description_is_rendered_as_sanitized_html(): void
    {
        $service = $this->service([
            'description' => 'وصف بديل للخدمة',
            'rich_description' => '<p>تفاصيل <strong>مهمة</strong></p><script>alert("x")</script><img src=x onerror=alert(1)>',
        ]);
        $html = $this->get('/services/'.$service->slug)->assertOk()->getContent();

        preg_match('/<article class="service-detail-content">(.*?)<\/article>/s', $html, $content);
        $this->assertNotEmpty($content[1] ?? null);
        $this->assertStringContainsString('<p>تفاصيل <strong>مهمة</strong></p>', $content[1]);
        $this->assertStringNotContainsString('<script', $content[1]);
        $this->assertStringNotContainsString('onerror=', $content[1]);
    }

    public function test_service_booking_link_targets_published_appointment_with_encoded_service_name(): void
    {
        $service = $this->service(['name' => 'رعاية & متابعة الحمل']);
        $appointment = SitePage::where('page_key', 'appointment')->firstOrFail();
        $appointment->update(['is_published' => true]);

        $html = $this->get('/services/'.$service->slug)->assertOk()->getContent();
        preg_match_all('/<a\b[^>]*class="[^"]*service-appointment-link[^"]*"[^>]*href="([^"]+)"[^>]*>/i', $html, $matches);

        $this->assertCount(2, $matches[1]);
        foreach ($matches[1] as $link) {
            $href = html_entity_decode($link, ENT_QUOTES, 'UTF-8');
            $this->assertStringStartsWith($appointment->url, $href);
            parse_str((string) parse_url($href, PHP_URL_QUERY), $query);
            $this->assertSame($service->name, $query['service'] ?? null);
        }
    }

    public function test_unpublished_appointment_hides_service_booking_link(): void
    {
        $service = $this->service();
        SitePage::where('page_key', 'appointment')->firstOrFail()->update(['is_published' => false]);

        $html = $this->get('/services/'.$service->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('service-appointment-link', $html);
    }

    public function test_service_related_branches_only_include_branches_linked_to_that_service(): void
    {
        $service = $this->service();
        $otherService = $this->service();
        $matching = ClinicBranch::create(['name' => 'فرع يقدم الخدمة', 'slug' => 'service-matching-branch', 'description' => 'وصف', 'address' => 'عنوان الاختبار', 'service_ids' => [$service->id], 'is_active' => true]);
        $unrelated = ClinicBranch::create(['name' => 'فرع لا يقدم الخدمة', 'slug' => 'service-unrelated-branch', 'description' => 'وصف', 'address' => 'عنوان الاختبار', 'service_ids' => [$otherService->id], 'is_active' => true]);

        $this->get('/services/'.$service->slug)->assertOk()->assertSee($matching->name)->assertDontSee($unrelated->name);
    }

    public function test_service_page_keeps_canonical_and_seo_metadata(): void
    {
        $service = $this->service();
        $html = $this->get('/services/'.$service->slug.'?utm_source=test')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<link rel="canonical" href=/i', $html));
        $this->assertStringContainsString('href="'.e(url('/services/'.$service->slug)).'"', $html);
        $this->assertSame(1, preg_match_all('/<title>/', $html));
        $this->assertStringContainsString('<meta name="description"', $html);
    }

    public function test_article_and_branch_detail_pages_keep_one_heading_and_their_content(): void
    {
        $article = Article::create(['title' => 'مقال الاختبار', 'slug' => 'service-design-article', 'content' => '<p>محتوى المقال</p>', 'is_published' => true]);
        $branch = ClinicBranch::create(['name' => 'فرع الاختبار', 'slug' => 'service-design-branch', 'description' => 'وصف الفرع', 'address' => 'عنوان الاختبار', 'service_ids' => [], 'is_active' => true]);

        foreach ([['/blog/'.$article->slug, 'محتوى المقال'], ['/branches/'.$branch->slug, 'وصف الفرع']] as [$path, $content]) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, preg_match_all('/<h1(?:\s[^>]*)?>/i', $html));
            $this->assertStringContainsString($content, $html);
        }
    }
}
