<?php

namespace Database\Seeders;

use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\SiteNavigation;
use App\Models\SitePage;
use Illuminate\Database\Seeder;

class MultipageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['home', 'home', 'الرئيسية', PageSection::whereNotIn('section_key', ['header', 'footer'])->orderBy('order')->pluck('section_key')->all()], ['about', 'about-us', 'عن د. محمد هلال', ['about']],
            ['services', 'services-index', 'خدمات العيادة', ['services']], ['journey', 'patient-journey', 'رحلة المريضة', ['your-journey']],
            ['videos', 'gallery', 'فيديوهات الدكتور', ['videos']], ['articles', 'articles-index', 'المقالات الطبية', ['articles']],
            ['branches', 'branches-index', 'فروع العيادة', ['clinics']], ['faq', 'frequently-asked-questions', 'الأسئلة الشائعة', ['faq']],
            ['contact', 'contact-us', 'تواصل معنا', ['clinics']], ['appointment', 'appointment', 'طلب موعد', ['booking']],
        ];
        foreach ($pages as [$key,$slug,$title,$sections]) {
            $available = $key === 'home' || count($sections) === PageSection::whereIn('section_key', $sections)->count();
            SitePage::firstOrCreate(['page_key' => $key], ['slug' => $slug, 'title' => $title, 'sections' => $sections, 'is_published' => $available, 'published_at' => $available ? now() : null,
                'meta_title' => $title.' | د. محمد هلال', 'meta_description' => 'تعرّفي على '.$title.' في عيادات د. محمد هلال لأمراض النساء والتوليد، واطلبي موعدًا في الفرع المناسب لكِ.']);
        }
        $aboutPage = SitePage::where('page_key', 'about')->first();
        if ($aboutPage && $aboutPage->doctor_profile === null) {
            $aboutPage->update(['doctor_profile' => json_decode(file_get_contents(database_path('content/doctor-profile.json')), true)]);
        }
        $items = collect(['home', 'about', 'services', 'journey', 'videos', 'articles', 'branches', 'faq', 'contact', 'appointment'])->map(fn ($key) => ['page_key' => $key, 'label' => SitePage::where('page_key', $key)->value('title')])->all();
        foreach (['header', 'footer'] as $location) {
            SiteNavigation::firstOrCreate(['location' => $location], ['items' => $location === 'header' ? collect($items)->whereIn('page_key', ['home', 'about', 'services', 'articles', 'branches', 'appointment'])->map(fn ($item) => array_replace($item, ['label' => ['home' => 'الرئيسية', 'about' => 'عن الدكتور', 'services' => 'الخدمات', 'articles' => 'المقالات', 'branches' => 'الفروع', 'appointment' => 'طلب موعد'][$item['page_key']]]))->values()->all() : $items]);
        }
        foreach (ClinicBranch::whereNull('slug')->get() as $branch) {
            $branch->update(['slug' => 'branch-'.$branch->id]);
        }
    }
}
