<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Services\SeoContentReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoContentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_readiness_reports_missing_review_data_without_inventing_it(): void
    {
        $this->seed();
        $article = Article::create(['title' => 'مقال يحتاج مراجعة', 'slug' => 'needs-review', 'is_published' => true]);
        $messages = implode(' ', array_column(SeoContentReadiness::issues(), 'message'));
        $this->assertStringContainsString('مقال يحتاج مراجعة', $messages);
        $this->assertStringContainsString('المراجع الطبي', $messages);
        $article->update(['author_name' => 'كاتب فعلي', 'medical_reviewer' => 'مراجع فعلي', 'reviewed_at' => now()->subDay(), 'meta_description' => 'وصف فعلي', 'sources' => [['title' => 'مصدر', 'url' => 'https://example.org']]]);
        $this->assertStringNotContainsString('مقال يحتاج مراجعة', implode(' ', array_column(SeoContentReadiness::issues(), 'message')));
    }
}
