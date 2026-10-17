<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PageSeoIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function assertSingleMetadataTags(string $html): void
    {
        $this->assertSame(1, substr_count($html, '<title>'));
        $this->assertSame(1, substr_count($html, 'name="description"'));
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertSame(1, substr_count($html, 'property="og:title"'));
        $this->assertSame(1, substr_count($html, 'property="og:image"'));
    }

    public function test_service_detail_metadata_is_isolated_from_legacy_seo_page(): void
    {
        $service = Service::first();
        $service->update(['meta_title' => 'عنوان خدمة مخصص', 'meta_description' => 'وصف خدمة مخصص للعزل', 'image_path' => 'landing/service-isolated.jpg']);
        SeoPage::create([
            'page_key' => 'service', 'page_label' => 'خدمة قديمة',
            'meta_title' => 'عنوان SEO قديم للخدمات', 'meta_description' => 'وصف SEO قديم للخدمات',
            'og_title' => 'عنوان OG قديم للخدمات', 'og_description' => 'وصف OG قديم للخدمات',
            'og_image' => 'landing/legacy-service.jpg', 'robots' => 'index_follow',
        ]);

        $html = $this->get('/services/'.$service->slug)->assertOk()->getContent();

        $this->assertSingleMetadataTags($html);
        $this->assertStringContainsString('<title>عنوان خدمة مخصص</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="وصف خدمة مخصص للعزل">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/services/'.$service->slug).'">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="عنوان خدمة مخصص">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="وصف خدمة مخصص للعزل">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('storage/landing/service-isolated.jpg').'">', $html);
        $this->assertStringNotContainsString('legacy-service', $html);
        $this->assertStringNotContainsString('عنوان OG قديم للخدمات', $html);
        $this->assertStringNotContainsString('وصف OG قديم للخدمات', $html);
        $this->assertStringNotContainsString('عنوان SEO قديم للخدمات', $html);
    }

    public function test_article_detail_open_graph_belongs_to_the_article_not_legacy_seo(): void
    {
        $article = Article::create([
            'title' => 'مقال العزل', 'slug' => 'isolation-article', 'content' => 'محتوى مقال العزل',
            'is_published' => true, 'published_at' => now()->subDay(),
            'meta_title' => 'عنوان مقال مخصص', 'meta_description' => 'وصف مقال مخصص للعزل',
            'og_image' => 'landing/article-isolated.jpg',
        ]);
        SeoPage::create([
            'page_key' => 'article', 'page_label' => 'مقال قديم',
            'meta_title' => 'عنوان SEO قديم للمقالات', 'meta_description' => 'وصف SEO قديم للمقالات',
            'og_title' => 'عنوان OG قديم للمقالات', 'og_description' => 'وصف OG قديم للمقالات',
            'og_image' => 'landing/legacy-article.jpg', 'robots' => 'index_follow',
        ]);

        $html = $this->get('/blog/'.$article->slug)->assertOk()->getContent();

        $this->assertSingleMetadataTags($html);
        $this->assertStringContainsString('<title>عنوان مقال مخصص</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="وصف مقال مخصص للعزل">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/blog/isolation-article').'">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="عنوان مقال مخصص">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="وصف مقال مخصص للعزل">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('storage/landing/article-isolated.jpg').'">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringNotContainsString('legacy-article', $html);
        $this->assertStringNotContainsString('عنوان OG قديم للمقالات', $html);
        $this->assertStringNotContainsString('وصف OG قديم للمقالات', $html);
        $this->assertStringNotContainsString('عنوان SEO قديم للمقالات', $html);
    }

    public function test_branch_detail_does_not_inherit_legacy_seo_page_metadata(): void
    {
        $branch = ClinicBranch::first();
        $branch->update(['meta_title' => 'عنوان فرع مخصص', 'meta_description' => 'وصف فرع مخصص للعزل']);
        SeoPage::create([
            'page_key' => 'branch', 'page_label' => 'فرع قديم',
            'og_title' => 'عنوان OG قديم للفروع', 'og_description' => 'وصف OG قديم للفروع',
            'og_image' => 'landing/legacy-branch.jpg', 'robots' => 'index_follow',
        ]);

        $html = $this->get('/branches/'.$branch->slug)->assertOk()->getContent();

        $this->assertSingleMetadataTags($html);
        $this->assertStringContainsString('<title>عنوان فرع مخصص</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="وصف فرع مخصص للعزل">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/branches/'.$branch->slug).'">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="عنوان فرع مخصص">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="وصف فرع مخصص للعزل">', $html);
        $this->assertStringNotContainsString('legacy-branch', $html);
        $this->assertStringNotContainsString('عنوان OG قديم للفروع', $html);
        $this->assertStringNotContainsString('وصف OG قديم للفروع', $html);
    }

    public function test_generic_pages_keep_legacy_seo_overrides_and_draft_preview_semantics(): void
    {
        $legacy = SeoPage::create([
            'page_key' => 'about', 'page_label' => 'عن الدكتور قديم',
            'meta_title' => 'عنوان SEO قديم لعن الدكتور', 'meta_description' => 'وصف SEO قديم لعن الدكتور',
            'og_title' => 'عنوان OG قديم لعن الدكتور', 'og_description' => 'وصف OG قديم لعن الدكتور',
            'og_image' => 'landing/legacy-about.jpg', 'robots' => 'index_follow',
        ]);

        $html = $this->get('/about-us')->assertOk()->getContent();

        $this->assertSingleMetadataTags($html);
        $this->assertStringContainsString('<title>عنوان SEO قديم لعن الدكتور</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="وصف SEO قديم لعن الدكتور">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="عنوان OG قديم لعن الدكتور">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="وصف OG قديم لعن الدكتور">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="'.asset('storage/landing/legacy-about.jpg').'">', $html);

        $legacy->update(['draft_data' => ['meta_title' => 'مسودة عنوان عن الدكتور']]);
        $this->get('/about-us')->assertOk()->assertDontSee('مسودة عنوان عن الدكتور');
        $preview = URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => SitePage::where('page_key', 'about')->firstOrFail()->id, 'preview' => 1]);
        $this->get($preview)->assertOk()->assertSee('<title>مسودة عنوان عن الدكتور</title>', false);
    }
}
