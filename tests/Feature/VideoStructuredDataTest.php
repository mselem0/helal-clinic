<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VideoStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    private function videoObjectsIn(string $theme): array
    {
        $page = SitePage::where('page_key', 'videos')->firstOrFail();
        $url = URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1, 'theme' => $theme]);
        $html = $this->get($url)->assertOk()->getContent();
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
        $this->assertNotEmpty($match, 'JSON-LD script missing in '.$theme.' render.');
        $schema = json_decode($match[1], true);
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('@graph', $schema);

        return array_values(array_filter($schema['@graph'], fn ($node) => ($node['@type'] ?? null) === 'VideoObject'));
    }

    private function makeVideo(array $attributes = []): Video
    {
        return Video::create(array_merge([
            'title' => 'فيديو بيانات منظمة',
            'description' => 'وصف فيديو البيانات المنظمة',
            'image_path' => 'landing/structured-thumb.jpg',
            'uploaded_at' => now()->subDays(2),
            'is_active' => true,
            'video_path' => 'landing/clinic-tour.mp4',
        ], $attributes));
    }

    public function test_vimeo_external_source_wins_over_obsolete_local_file_and_is_embed_url_in_both_themes(): void
    {
        $video = $this->makeVideo([
            'title' => 'فيديو فيميو خارجي',
            'external_video_url' => 'https://vimeo.com/999888777',
            'video_path' => 'assets/reel-1122351437148430.mp4',
            'reel' => '1122351437148430',
        ]);

        foreach (['helal_classic', 'helal_modern'] as $theme) {
            $objects = $this->videoObjectsIn($theme);
            $this->assertCount(1, $objects, 'Only the complete record should be emitted in '.$theme.'.');
            $this->assertSame('فيديو فيميو خارجي', $objects[0]['name']);
            $this->assertSame('وصف فيديو البيانات المنظمة', $objects[0]['description']);
            $this->assertSame('https://player.vimeo.com/video/999888777', $objects[0]['embedUrl']);
            $this->assertArrayNotHasKey('contentUrl', $objects[0]);
            $this->assertSame(asset('storage/landing/structured-thumb.jpg'), $objects[0]['thumbnailUrl']);
            $this->assertStringStartsWith('http', $objects[0]['thumbnailUrl']);
            $this->assertSame($video->fresh()->uploaded_at->toIso8601String(), $objects[0]['uploadDate']);
        }
    }

    public function test_local_video_file_is_emitted_as_content_url(): void
    {
        $this->makeVideo([
            'title' => 'فيديو محلي',
            'video_path' => 'landing/clinic-tour.mp4',
            'reel' => null,
            'external_video_url' => null,
        ]);

        $objects = $this->videoObjectsIn('helal_classic');

        $this->assertCount(1, $objects);
        $this->assertSame(asset('storage/landing/clinic-tour.mp4'), $objects[0]['contentUrl']);
        $this->assertArrayNotHasKey('embedUrl', $objects[0]);
    }

    public function test_bundled_reel_exposes_native_content_url_in_structured_data(): void
    {
        $this->makeVideo([
            'title' => 'فيديو ريل قديم',
            'video_path' => 'assets/reel-2129128044360416.mp4',
            'reel' => '2129128044360416',
            'external_video_url' => null,
        ]);

        $objects = $this->videoObjectsIn('helal_classic');

        $this->assertCount(1, $objects);
        $this->assertSame(url('assets/reel-2129128044360416.mp4'), $objects[0]['contentUrl']);
        $this->assertArrayNotHasKey('embedUrl', $objects[0]);
    }

    public function test_journey_bootstrap_uses_resolved_local_video_sources_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $html = $this->get('/patient-journey')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~window\.helalJourney = (.*?); window\.helalBooking~s', $html);
        preg_match('~window\.helalJourney = (.*?); window\.helalBooking~s', $html, $match);
        $journey = json_decode($match[1], true);

        $this->assertIsArray($journey);
        $this->assertStringEndsWith('/local-video/2129128044360416.mp4', $journey['planning']['source']);
        $this->assertStringEndsWith('/local-video/1122351437148430.mp4', $journey['pregnant']['source']);
        $this->assertSame('', $journey['waiting']['source']);
    }

    public function test_incomplete_future_or_inactive_videos_are_omitted(): void
    {
        $this->makeVideo(['title' => 'فيديو مكتمل للمرجع', 'video_path' => 'landing/clinic-tour.mp4']);
        $this->makeVideo(['title' => 'بدون صورة', 'image_path' => null]);
        $this->makeVideo(['title' => 'بدون تاريخ نشر', 'uploaded_at' => null]);
        $this->makeVideo(['title' => 'بدون مصدر تشغيل', 'video_path' => null, 'reel' => null, 'external_video_url' => null]);
        $this->makeVideo(['title' => '', 'video_path' => 'landing/clinic-tour.mp4']);
        $this->makeVideo(['title' => 'تاريخ مستقبلي', 'uploaded_at' => now()->addDay()]);
        $this->makeVideo(['title' => 'فيديو غير مفعل', 'is_active' => false, 'video_path' => 'landing/clinic-tour.mp4']);

        $objects = $this->videoObjectsIn('helal_modern');

        $this->assertCount(1, $objects, 'Seeded videos without an upload date and every incomplete record must be omitted.');
        $this->assertSame('فيديو مكتمل للمرجع', $objects[0]['name']);
    }

    public function test_hidden_video_section_does_not_emit_video_schema(): void
    {
        $this->makeVideo(['video_path' => 'landing/clinic-tour.mp4']);
        SitePage::where('page_key', 'videos')->update(['sections' => []]);
        $this->assertSame([], $this->videoObjectsIn('helal_classic'));
    }
}
