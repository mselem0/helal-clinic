<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('site_pages')->where('page_key', 'about')->first();
        if (! $page) {
            return;
        }
        $profile = json_decode($page->doctor_profile ?? '{}', true) ?: [];
        if (! empty($profile['official_links'])) {
            return;
        }
        $profile['official_links'] = [
            ['label' => 'فيسبوك', 'url' => 'https://www.facebook.com/HelalClinic0/'],
            ['label' => 'إنستجرام', 'url' => 'https://www.instagram.com/dr.mohamedhelal.clinic/'],
            ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@mohammedhelal769'],
        ];
        DB::table('site_pages')->where('id', $page->id)->update(['doctor_profile' => json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'public_updated_at' => now()]);
    }

    public function down(): void
    {
        // Preserve confirmed content and subsequent editorial changes.
    }
};
