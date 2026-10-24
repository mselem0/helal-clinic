<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_manager_loads_once_on_published_production_pages_only(): void
    {
        $this->seed();
        app()->detectEnvironment(fn () => 'production');

        $this->get('/')
            ->assertOk()
            ->assertSee("'GTM-5HD4L3FH'", false)
            ->assertSee('www.googletagmanager.com/gtm.js', false);
        $this->assertSame(1, substr_count($this->get('/')->getContent(), 'GTM-5HD4L3FH'));

        $preview = URL::temporarySignedRoute('home', now()->addMinutes(5), ['preview' => 1]);
        $this->get($preview)->assertOk()->assertDontSee('GTM-5HD4L3FH', false);

        app()->detectEnvironment(fn () => 'local');
        $this->get('/')->assertOk()->assertDontSee('GTM-5HD4L3FH', false);
    }
}
