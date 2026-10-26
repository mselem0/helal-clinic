<?php

namespace Tests\Feature;

use App\Models\SeoRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdsNoIndexTest extends TestCase
{
    use RefreshDatabase;

    private const ROBOTS_HEADER = 'noindex, nofollow, noarchive, nosnippet, noimageindex';

    public function test_public_pages_errors_robots_and_sitemap_responses_are_never_indexable(): void
    {
        $this->seed();

        $this->get('/')->assertOk()->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER)
            ->assertSee('<meta name="robots" content="noindex,nofollow"', false);

        $this->get('/not-a-real-page')->assertNotFound()->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER);
        $this->get('/robots.txt')->assertOk()->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER)
            ->assertDontSee('Sitemap:', false)
            ->assertDontSee("Disallow: /\n", false);

        foreach (['/sitemap.xml', '/sitemap-1.xml'] as $path) {
            $this->get($path)->assertNotFound()->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER);
        }
    }

    public function test_redirect_responses_are_never_indexable(): void
    {
        SeoRedirect::create([
            'source_url' => '/old-page',
            'target_url' => '/',
            'redirect_type' => 301,
            'match_type' => 'exact',
            'is_active' => true,
        ]);

        $this->get('/old-page')->assertRedirect('/')->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER);
    }

    public function test_server_error_responses_are_never_indexable(): void
    {
        config(['app.debug' => false]);
        Route::get('/', fn () => throw new \RuntimeException('Intentional test error'));

        $this->get('/')->assertServerError()
            ->assertHeader('X-Robots-Tag', self::ROBOTS_HEADER);
    }
}
