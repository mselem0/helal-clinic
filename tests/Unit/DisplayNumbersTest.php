<?php

namespace Tests\Unit;

use App\Support\DisplayNumbers;
use PHPUnit\Framework\TestCase;

class DisplayNumbersTest extends TestCase
{
    public function test_text_converts_arabic_and_persian_digits_and_numeric_separators(): void
    {
        $this->assertSame('0123456789 / 0123456789', DisplayNumbers::text('٠١٢٣٤٥٦٧٨٩ / ۰۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('1,234.56', DisplayNumbers::text('١٬٢٣٤٫٥٦'));
        $this->assertSame('Arabic text 123', DisplayNumbers::text('Arabic text ١٢٣'));
    }

    public function test_html_converts_only_visible_text_and_keeps_protected_html_segments_intact(): void
    {
        $html = '<p id="item-١٢٣" data-url="https://example.test/a>b/٤٥">العربية ١٢٣٬٤٥٦٫٧٨ &amp; &#1633;</p>'
            .'<!-- comment ٤٥٦ -->'
            .'<script>const account = "١٢٣";</script>'
            .'<style>.item::after { content: "٤٥٦"; }</style>'
            .'<b dir="rtl" aria-label="رقم ٧">۱۲۳</b>';

        $expected = '<p id="item-١٢٣" data-url="https://example.test/a>b/٤٥">العربية 123,456.78 &amp; &#1633;</p>'
            .'<!-- comment ٤٥٦ -->'
            .'<script>const account = "١٢٣";</script>'
            .'<style>.item::after { content: "٤٥٦"; }</style>'
            .'<b dir="rtl" aria-label="رقم ٧">123</b>';

        $this->assertSame($expected, DisplayNumbers::html($html));
    }
}
