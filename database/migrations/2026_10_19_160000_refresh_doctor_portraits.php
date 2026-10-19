<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['home' => ['field_15', 'assets/portrait-clinic.webp'], 'about' => ['field_1', 'assets/portrait-office.webp']] as $key => [$field, $image]) {
            $section = DB::table('page_sections')->where('section_key', $key)->first();
            if (! $section) continue;
            $body = json_decode($section->body, true) ?: [];
            foreach ($body['fields'] ?? [] as $index => $entry) {
                if (($entry['key'] ?? '') === $field) $body['fields'][$index]['value'] = $image;
            }
            DB::table('page_sections')->where('id', $section->id)->update(['body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'public_updated_at' => now()]);
        }
        foreach (['service-1' => 'portrait-ultrasound', 'service-3' => 'portrait-surgery'] as $slug => $image) {
            DB::table('services')->where('slug', $slug)->update(['image_path' => 'assets/'.$image.'.webp', 'updated_at' => now()]);
        }
        $page = DB::table('site_pages')->where('page_key', 'about')->first();
        if ($page) {
            $profile = json_decode($page->doctor_profile ?? '{}', true) ?: [];
            $profile['image'] = 'assets/portrait-conference.webp';
            DB::table('site_pages')->where('id', $page->id)->update(['doctor_profile' => json_encode($profile, JSON_UNESCAPED_UNICODE), 'public_updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Preserve editorial content; originals remain available in assets.
    }
};
