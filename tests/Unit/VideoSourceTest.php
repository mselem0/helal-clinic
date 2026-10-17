<?php

namespace Tests\Unit;

use App\Support\VideoSource;
use Tests\TestCase;

class VideoSourceTest extends TestCase
{
    public function test_supplied_videos_map_to_the_correct_vimeo_ids(): void
    {
        foreach (VideoSource::VIMEO as $reel => $id) {
            $this->assertSame('https://player.vimeo.com/video/'.$id, VideoSource::resolve('assets/reel-'.$reel.'.mp4', (string) $reel));
        }
    }

    public function test_custom_files_override_defaults_and_vimeo_links_are_normalized(): void
    {
        $this->assertSame('https://example.org/custom.mp4', VideoSource::resolve('https://example.org/custom.mp4', '1122351437148430'));
        $this->assertSame('https://player.vimeo.com/video/1236786560?h=abc123', VideoSource::resolve('https://vimeo.com/1236786560?h=abc123&app_id=58479'));
    }
}
