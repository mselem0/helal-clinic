<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Service;
use Database\Seeders\ReviewedArticlesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleInternalLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_reviewed_article_seeder_adds_the_guarded_service_associations(): void
    {
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);

        $this->assertSame([Service::where('slug', 'service-1')->value('id')], Article::where('slug', 'article-1')->firstOrFail()->service_ids);
        $this->assertSame([Service::where('slug', 'service-2')->value('id')], Article::where('slug', 'article-2')->firstOrFail()->service_ids);
        $this->assertSame([Service::where('slug', 'service-3')->value('id')], Article::where('slug', 'article-3')->firstOrFail()->service_ids);
    }

    public function test_reviewed_article_link_migration_only_fills_empty_exact_matches(): void
    {
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);

        $pregnancyService = Service::where('slug', 'service-1')->firstOrFail();
        Article::whereIn('slug', ['article-1', 'gestational-diabetes-birth-options'])->update(['service_ids' => null]);
        Article::where('slug', 'article-2')->update(['service_ids' => json_encode([$pregnancyService->id])]);
        Article::where('slug', 'article-3')->update(['title' => 'مقال بعنوان مخصص', 'service_ids' => json_encode([$pregnancyService->id])]);

        $migration = require database_path('migrations/2026_10_24_140000_link_reviewed_articles_and_services.php');
        $migration->up();

        $this->assertSame([$pregnancyService->id], Article::where('slug', 'article-1')->firstOrFail()->service_ids);
        $this->assertSame([$pregnancyService->id], Article::where('slug', 'article-2')->firstOrFail()->service_ids);
        $this->assertSame([$pregnancyService->id], Article::where('slug', 'article-3')->firstOrFail()->service_ids);
        $this->assertSame([$pregnancyService->id], Article::where('slug', 'gestational-diabetes-birth-options')->firstOrFail()->service_ids);
    }

    public function test_reviewed_articles_link_to_their_service_and_relevant_published_reading(): void
    {
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);

        $service = Service::where('slug', 'service-1')->firstOrFail();
        Article::where('slug', 'article-1')->update(['service_ids' => json_encode([$service->id])]);
        Article::where('slug', 'gestational-diabetes-birth-options')->update(['service_ids' => json_encode([$service->id])]);
        Article::where('slug', 'postpartum-emergency-warning-signs')->update(['is_published' => false]);

        $this->get('/blog/article-1')->assertOk()
            ->assertSee(route('services.show', $service->slug), false)
            ->assertSee(route('blog.show', 'gestational-diabetes-birth-options'), false)
            ->assertDontSee(route('blog.show', 'postpartum-emergency-warning-signs'), false);

        $this->get('/services/'.$service->slug)->assertOk()
            ->assertSee(route('blog.show', 'article-1'), false)
            ->assertSee('أول موعد لمتابعة الحمل: كيف تستعدين؟');
    }

    public function test_future_articles_are_not_shown_as_service_reading(): void
    {
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);

        $service = Service::where('slug', 'service-1')->firstOrFail();
        Article::where('slug', 'article-1')->update(['service_ids' => json_encode([$service->id]), 'published_at' => now()->addDay()]);

        $this->get('/services/'.$service->slug)->assertOk()
            ->assertDontSee(route('blog.show', 'article-1'), false);
    }

    public function test_curated_reading_requires_the_reviewed_source_and_target_titles(): void
    {
        $this->seed();
        $this->seed(ReviewedArticlesSeeder::class);

        Article::where('slug', 'article-1')->update(['title' => 'مقال حمل بعنوان مخصص']);
        $this->get('/blog/article-1')->assertOk()
            ->assertDontSee(route('blog.show', 'gestational-diabetes-birth-options'), false);

        Article::where('slug', 'article-1')->update(['title' => 'أول موعد لمتابعة الحمل: كيف تستعدين؟']);
        Article::where('slug', 'gestational-diabetes-birth-options')->update(['title' => 'مقال بعنوان مخصص']);
        $this->get('/blog/article-1')->assertOk()
            ->assertDontSee(route('blog.show', 'gestational-diabetes-birth-options'), false);
    }
}
