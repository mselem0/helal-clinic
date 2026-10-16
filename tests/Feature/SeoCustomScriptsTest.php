<?php

namespace Tests\Feature;

use App\Models\SeoGlobalSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SeoCustomScriptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_header_directive_renders_active_header_scripts()
    {
        $scripts = [
            [
                'name' => 'Header Script 1',
                'code' => '<script>console.log("header 1");</script>',
                'location' => 'header',
                'is_active' => true,
            ],
            [
                'name' => 'Header Script 2',
                'code' => '<script>console.log("header 2");</script>',
                'location' => 'header',
                'is_active' => false, // Inactive
            ],
            [
                'name' => 'Footer Script',
                'code' => '<script>console.log("footer");</script>',
                'location' => 'footer',
                'is_active' => true,
            ],
        ];

        SeoGlobalSetting::create([
            'key' => 'custom_scripts',
            'value' => json_encode($scripts),
            'group' => 'scripts',
        ]);

        $rendered = Blade::render('@seoHeader');

        $this->assertStringContainsString('<script>console.log("header 1");</script>', $rendered);
        $this->assertStringNotContainsString('<script>console.log("header 2");</script>', $rendered);
        $this->assertStringNotContainsString('<script>console.log("footer");</script>', $rendered);
    }

    public function test_seo_footer_directive_renders_active_footer_scripts()
    {
        $scripts = [
            [
                'name' => 'Header Script',
                'code' => '<script>console.log("header");</script>',
                'location' => 'header',
                'is_active' => true,
            ],
            [
                'name' => 'Footer Script 1',
                'code' => '<script>console.log("footer 1");</script>',
                'location' => 'footer',
                'is_active' => true,
            ],
            [
                'name' => 'Footer Script 2',
                'code' => '<script>console.log("footer 2");</script>',
                'location' => 'footer',
                'is_active' => false, // Inactive
            ],
        ];

        SeoGlobalSetting::create([
            'key' => 'custom_scripts',
            'value' => json_encode($scripts),
            'group' => 'scripts',
        ]);

        $rendered = Blade::render('@seoFooter');

        $this->assertStringContainsString('<script>console.log("footer 1");</script>', $rendered);
        $this->assertStringNotContainsString('<script>console.log("footer 2");</script>', $rendered);
        $this->assertStringNotContainsString('<script>console.log("header");</script>', $rendered);
    }
}
