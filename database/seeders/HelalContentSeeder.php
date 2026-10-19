<?php

namespace Database\Seeders;

use App\Models\AboutDoctor;
use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\HeroSetting;
use App\Models\PageSection;
use App\Models\SeoGlobalSetting;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HelalContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (json_decode(file_get_contents(database_path('content/helal.json')), true, flags: JSON_THROW_ON_ERROR) as $section) {
            PageSection::firstOrCreate(['section_key' => $section['key']], [
                'section_label' => $section['label'], 'order' => $section['order'],
                'is_active' => true, 'body' => json_encode(['fields' => $section['fields'], 'faqs' => $section['faqs'] ?? [], 'stages' => $section['key'] === 'your-journey' ? json_decode(file_get_contents(database_path('content/journey.json')), true) : []], JSON_UNESCAPED_UNICODE),
            ]);
        }
        SeoPage::firstOrCreate(['page_key' => 'home'], ['page_label' => 'الرئيسية', 'meta_title' => 'د. محمد هلال | استشاري أمراض النساء والتوليد والحقن المجهري', 'meta_description' => 'د. محمد هلال، مدرس واستشاري أمراض النساء والتوليد والحقن المجهري. حاصل على دكتوراه وماجستير أمراض النساء والتوليد من كلية الطب بجامعة الأزهر.', 'robots' => 'index_follow']);
        SeoGlobalSetting::firstOrCreate(['key' => 'sitemap_enabled'], ['value' => '1', 'group' => 'general']);
        HeroSetting::firstOrCreate([], ['doctor_name' => 'د. محمد هلال', 'doctor_title' => 'استشاري أمراض النساء والتوليد', 'doctor_university' => '', 'badge_text' => 'عيادات هلال', 'whatsapp_button_text' => 'تواصل عبر واتساب', 'booking_button_text' => 'طلب موعد', 'whatsapp_number' => '201017900354']);
        AboutDoctor::firstOrCreate([], ['bio_title' => 'عن د. محمد هلال', 'bio_text' => 'استشاري أمراض النساء والتوليد والمناظير والحقن المجهري.']);
        SiteSetting::firstOrCreate(['key' => 'active_theme'], ['group' => 'appearance', 'value' => 'helal_classic']);
        SiteSetting::firstOrCreate(['key' => 'wa_number'], ['group' => 'whatsapp', 'value' => '201017900354']);
        foreach (['helal_classic', 'helal_modern'] as $theme) {
            SiteSetting::firstOrCreate(['key' => 'theme_'.$theme], ['group' => 'appearance', 'value' => json_encode(['primary' => '#006f76', 'accent' => '#ed8b19'])]);
        }
        SiteSetting::firstOrCreate(['key' => 'content_review_required'], ['group' => 'appearance', 'value' => '1']);
        File::ensureDirectoryExists(storage_path('app/public/assets'));
        foreach (File::files(public_path('assets')) as $file) {
            $target = storage_path('app/public/assets/'.$file->getFilename());
            if (! file_exists($target)) {
                File::copy($file->getPathname(), $target);
            }
        }
        $collections = json_decode(file_get_contents(database_path('content/collections.json')), true);
        foreach ($collections['services'] as $i => $row) {
            Service::firstOrCreate(['slug' => 'service-'.($i + 1)], ['name' => $row['name'], 'card_title' => $row['title'], 'description' => $row['description'], 'image_path' => $row['image'], 'order' => $i, 'is_active' => true]);
        }
        foreach ($collections['clinics'] as $row) {
            ClinicBranch::firstOrCreate(['name' => $row['name']], ['address' => $row['address'], 'google_maps_url' => $row['google_maps_url'], 'order' => $row['order'], 'is_active' => true]);
        }
        foreach ($collections['videos'] as $row) {
            Video::firstOrCreate(['reel' => $row['reel']], ['title' => $row['title'], 'description' => $row['description'], 'category' => $row['category'], 'image_path' => $row['image'], 'video_path' => 'assets/reel-'.$row['reel'].'.mp4', 'order' => $row['order'], 'is_active' => true]);
        }
        foreach ($collections['reviews'] as $row) {
            Testimonial::firstOrCreate(['patient_name' => $row['patient_name']], ['patient_initial' => $row['patient_initial'], 'review_text' => $row['review_text'], 'source_url' => $row['source_url'], 'rating' => 5, 'order' => $row['order'], 'is_active' => true]);
        }
        foreach ($collections['articles'] as $i => $row) {
            Article::firstOrCreate(['slug' => 'article-'.($i + 1)], ['title' => $row['title'], 'content' => $row['description'], 'meta_description' => $row['description'], 'image_path' => $row['image'], 'is_published' => false]);
        }
        $this->call(MultipageSeeder::class);
    }
}
