<?php

namespace Tests\Feature;

use App\Models\Article;
use Database\Seeders\ReviewedArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReviewedArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_fertility_articles_remain_available_without_indexing_and_fertility_article_is_hidden(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);
        $this->assertSame(6, Article::where('is_published', true)->count());
        $index = $this->get('/blog')->assertOk();
        $this->get('/sitemap.xml')->assertNotFound();
        foreach (Article::all() as $article) {
            if ($article->slug === 'article-2') {
                continue;
            }
            $index->assertSee($article->title);
            $html = $this->get($article->publicPath())->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex')->getContent();
            $this->assertSame(1, substr_count($html, '<h1'));
            $this->assertStringContainsString(e($article->meta_title), $html);
            $this->assertStringContainsString(e($article->sources[0]['url']), $html);
            $this->assertStringNotContainsString('المراجعة الطبية:', $html);
            $this->assertNull($article->medical_reviewer);
            $this->assertFileExists(public_path($article->image_path));
        }
        $index->assertDontSee(Article::where('slug', 'article-2')->value('title'));
        $this->get('/blog/article-2')->assertNotFound();
        Article::where('slug', 'article-1')->update(['is_published' => false]);
        $this->get('/blog/article-1')->assertNotFound();
        $this->get('/sitemap.xml')->assertDontSee('/blog/article-1');
        $this->seed(ReviewedArticlesSeeder::class);
        $this->assertSame(6, Article::count());
        $this->assertFalse(Article::where('slug', 'article-1')->first()->is_published);
    }

    public function test_import_preserves_existing_editorial_changes(): void
    {
        $this->seed();
        Article::where('slug', 'article-1')->update(['content' => '<p>محتوى عدله المستخدم</p>']);
        Article::where('slug', 'article-2')->update(['meta_title' => 'عنوان مخصص']);
        $this->seed(ReviewedArticlesSeeder::class);
        $this->assertSame('<p>محتوى عدله المستخدم</p>', Article::where('slug', 'article-1')->value('content'));
        $this->assertSame('عنوان مخصص', Article::where('slug', 'article-2')->value('meta_title'));
    }
}
