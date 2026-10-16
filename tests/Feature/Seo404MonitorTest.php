<?php

namespace Tests\Feature;

use App\Models\Seo404Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Seo404MonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_404_errors_to_monitor()
    {
        $this->assertEquals(0, Seo404Monitor::count());

        $response = $this->get('/a-non-existent-url-12345');

        $response->assertStatus(404);

        $this->assertEquals(1, Seo404Monitor::count());
        $monitor = Seo404Monitor::first();
        $this->assertTrue(str_contains($monitor->url, '/a-non-existent-url-12345'));
    }
}
