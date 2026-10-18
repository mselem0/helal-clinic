<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MedicalArticleSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function graph(Article $article): array
    {
        $html = $this->get($article->publicPath())->assertOk()->getContent();
        preg_match('~<script type="application/ld\\+json">(.*?)</script>~s', $html, $matches);
        $this->assertNotEmpty($matches);

        return collect(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR)['@graph'])->keyBy('@type')->all();
    }

    public function test_visible_medical_review_and_sources_are_linked_to_article_schema(): void
    {
        $article = Article::create(['title' => 'مقال موثق', 'slug' => 'documented-article', 'is_published' => true, 'author_name' => 'كاتب فعلي', 'medical_reviewer' => 'مراجع فعلي', 'reviewed_at' => now()->subDay(), 'sources' => [['title' => 'مصدر طبي', 'url' => 'https://example.org/reference']]]);
        $graph = $this->graph($article);
        $this->assertSame('كاتب فعلي', $graph['Article']['author']['name']);
        $this->assertSame('مراجع فعلي', $graph['MedicalWebPage']['reviewedBy']['name']);
        $this->assertSame($article->reviewed_at->toDateString(), $graph['MedicalWebPage']['lastReviewed']);
        $this->assertSame($graph['Article']['@id'], $graph['MedicalWebPage']['mainEntity']['@id']);
        $this->assertSame('https://example.org/reference', $graph['Article']['citation'][0]['url']);
        $this->get($article->publicPath())->assertSee('كاتب فعلي')->assertSee('مراجع فعلي')->assertSee('مصدر طبي');
    }

    public function test_missing_review_fields_and_invalid_sources_are_not_invented(): void
    {
        $article = Article::create(['title' => 'مقال غير مراجع', 'slug' => 'unreviewed-article', 'is_published' => true, 'sources' => [['title' => 'غير صالح', 'url' => 'javascript:alert(1)']]]);
        $graph = $this->graph($article);
        $this->assertArrayNotHasKey('author', $graph['Article']);
        $this->assertArrayNotHasKey('citation', $graph['Article']);
        $this->assertArrayNotHasKey('reviewedBy', $graph['MedicalWebPage']);
        $this->assertArrayNotHasKey('lastReviewed', $graph['MedicalWebPage']);
        $article->update(['reviewed_at' => now()->addDay()]);
        $this->assertArrayNotHasKey('lastReviewed', $this->graph($article)['MedicalWebPage']);
    }
}
