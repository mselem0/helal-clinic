<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_ORDER = ['home', 'your-journey', 'services', 'about', 'videos', 'section-5', 'clinics', 'articles', 'reviews', 'booking', 'faq'];

    private const NEW_ORDER = ['home', 'services', 'about', 'your-journey', 'videos', 'reviews', 'section-5', 'clinics', 'articles', 'faq', 'booking'];

    public function up(): void
    {
        $this->replaceExactDefault(self::OLD_ORDER, self::NEW_ORDER);
    }

    public function down(): void
    {
        $this->replaceExactDefault(self::NEW_ORDER, self::OLD_ORDER);
    }

    private function replaceExactDefault(array $from, array $to): void
    {
        $page = DB::table('site_pages')->where('page_key', 'home')->first();

        if (! $page) {
            return;
        }

        $updates = [];
        if ($this->decode($page->sections) === $from) {
            $updates['sections'] = json_encode($to, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        $draftData = $this->decode($page->draft_data);
        if (is_array($draftData) && ($draftData['sections'] ?? null) === $from) {
            $draftData['sections'] = $to;
            $updates['draft_data'] = json_encode($draftData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        if ($updates !== []) {
            DB::table('site_pages')->where('id', $page->id)->update($updates);
        }
    }

    private function decode(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return json_decode($value, true);
    }
};
