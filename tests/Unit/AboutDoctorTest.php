<?php

namespace Tests\Unit;

use App\Models\AboutDoctor;
use PHPUnit\Framework\TestCase;

class AboutDoctorTest extends TestCase
{
    public function test_safe_bio_text_keeps_basic_formatting_and_removes_unsafe_markup(): void
    {
        $about = new AboutDoctor([
            'bio_text' => '<p onclick="alert(1)">Safe <strong>bio</strong></p><script>alert(1)</script><a href="javascript:alert(1)">bad</a>',
        ]);

        $this->assertSame('<p>Safe <strong>bio</strong></p>bad', $about->safe_bio_text);
    }
}
