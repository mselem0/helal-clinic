<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $title = 'د. محمد هلال | استشاري أمراض النساء والتوليد والحقن المجهري';
        $description = 'د. محمد هلال، مدرس واستشاري أمراض النساء والتوليد والحقن المجهري. دكتوراه وماجستير أمراض النساء والتوليد، كلية الطب بجامعة الأزهر.';
        foreach (['home' => $title, 'about' => 'عن د. محمد هلال | المؤهلات العلمية والتخصصات'] as $key => $pageTitle) {
            $data = ['meta_title' => $pageTitle, 'meta_description' => $description, 'og_image' => 'assets/portrait-office.webp', 'public_updated_at' => now()];
            DB::table('site_pages')->where('page_key', $key)->update($data);
            DB::table('seo_pages')->where('page_key', $key)->update($data + ['og_title' => $pageTitle, 'og_description' => $description]);
        }
        DB::table('seo_global_settings')->updateOrInsert(['key' => 'site_name'], ['value' => 'د. محمد هلال', 'group' => 'general', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Keep editorial changes; metadata can be revised from the admin panel.
    }
};
