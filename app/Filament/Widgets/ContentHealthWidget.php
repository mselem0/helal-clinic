<?php

// app/Filament/Widgets/ContentHealthWidget.php

namespace App\Filament\Widgets;

use App\Models\ClinicBranch;
use App\Models\HeroSetting;
use App\Models\SeoPage;
use App\Models\Service;
use App\Models\Testimonial;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Route;

class ContentHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.content-health-widget';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 1;

    public function getReadinessScore(): int
    {
        $checks = [
            'seo' => SeoPage::where('page_key', 'home')->whereNotNull('meta_title')->exists(),
            'services' => Service::where('is_active', true)->count() >= 3,
            'branches' => ClinicBranch::where('is_active', true)->count() > 0,
            'testimonials' => Testimonial::where('is_active', true)->count() >= 2,
            'photo' => HeroSetting::first()?->hasMedia('doctor-photo'),
        ];

        $passed = count(array_filter($checks));

        return round(($passed / count($checks)) * 100);
    }

    public function getHealthIssues(): array
    {
        $issues = [];

        // 1. SEO Checks
        $homeSeo = SeoPage::where('page_key', 'home')->first();
        if (! $homeSeo || empty($homeSeo->meta_title)) {
            $issues[] = [
                'type' => 'danger',
                'text' => 'إعدادات SEO للصفحة الرئيسية غير مكتملة.',
                'link' => Route::has('filament.admin.pages.seo-management') ? route('filament.admin.pages.seo-management', ['tab' => 'pages']) : null,
            ];
        }

        // 2. Services Checks
        if (Service::where('is_active', true)->count() < 3) {
            $issues[] = [
                'type' => 'warning',
                'text' => 'يُفضل إضافة 3 خدمات طبية على الأقل.',
                'link' => Route::has('filament.admin.resources.services.index') ? route('filament.admin.resources.services.index') : null,
            ];
        }

        // 3. Branches Checks
        if (ClinicBranch::where('is_active', true)->count() == 0) {
            $issues[] = [
                'type' => 'danger',
                'text' => 'لم يتم تفعيل أي فرع للعيادة بالموقع.',
                'link' => Route::has('filament.admin.resources.clinic-branches.index') ? route('filament.admin.resources.clinic-branches.index') : null,
            ];
        }

        // 4. Testimonials Checks
        if (Testimonial::where('is_active', true)->count() < 2) {
            $issues[] = [
                'type' => 'info',
                'text' => 'إضافة آراء المرضى تزيد من ثقة الزوار.',
                'link' => Route::has('filament.admin.resources.testimonials.index') ? route('filament.admin.resources.testimonials.index') : null,
            ];
        }

        // 5. Media Checks
        $hero = HeroSetting::first();
        if (! $hero || ! $hero->hasMedia('doctor-photo')) {
            $issues[] = [
                'type' => 'warning',
                'text' => 'صورة الطبيب مفقودة في قسم الهيرو.',
                'link' => Route::has('filament.admin.pages.theme-editor') ? route('filament.admin.pages.theme-editor', ['section' => 'hero']) : null,
            ];
        }

        return $issues;
    }
}
