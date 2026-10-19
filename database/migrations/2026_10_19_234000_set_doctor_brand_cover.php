<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['default_og_image' => 'assets/doctor-social-cover.jpg', 'hero_background_image' => 'assets/doctor-brand-banner.webp'] as $key => $value) {
            DB::table('seo_global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'group' => 'general', 'updated_at' => now()]);
        }
        foreach (['site_pages', 'seo_pages', 'services', 'articles', 'clinic_branches'] as $table) {
            DB::table($table)->update(['og_image' => 'assets/doctor-social-cover.jpg']);
        }
    }
    public function down(): void {}
};
