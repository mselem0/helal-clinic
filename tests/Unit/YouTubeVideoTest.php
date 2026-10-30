<?php

namespace Tests\Unit;

use App\Support\YouTubeVideo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YouTubeVideoTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function test_it_accepts_supported_youtube_links_and_returns_the_video_id(string $url, string $id): void
    {
        $this->assertSame($id, YouTubeVideo::id($url));
    }

    public static function validUrls(): array
    {
        return [
            ['https://youtu.be/e5T1gvR87wU?si=Eu2hs8w_G0Xg8PBk', 'e5T1gvR87wU'],
            ['https://www.youtube.com/watch?v=2HLeCv7_1xw&t=2', '2HLeCv7_1xw'],
            ['https://youtube.com/shorts/e5T1gvR87wU', 'e5T1gvR87wU'],
            ['https://www.youtube.com/embed/2HLeCv7_1xw', '2HLeCv7_1xw'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_it_rejects_non_youtube_urls_and_arbitrary_iframe_payloads(string $url): void
    {
        $this->assertNull(YouTubeVideo::id($url));
    }

    public static function invalidUrls(): array
    {
        return [
            ['https://example.com/embed/2HLeCv7_1xw'],
            ['https://youtube.com.evil.test/watch?v=e5T1gvR87wU'],
            ['javascript:alert(1)'],
            ['<iframe src="https://youtube.com/embed/e5T1gvR87wU"></iframe>'],
            ['https://youtu.be/too-short'],
            ['http://youtu.be/e5T1gvR87wU'],
            ['youtu.be/e5T1gvR87wU'],
            ['https://youtube.com@evil.test/watch?v=e5T1gvR87wU'],
            ['https://www.youtube.com.evil.test/watch?v=e5T1gvR87wU'],
        ];
    }
}
