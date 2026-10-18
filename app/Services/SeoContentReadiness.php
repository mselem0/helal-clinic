<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ClinicBranch;
use App\Models\SitePage;
use App\Models\Video;

class SeoContentReadiness
{
    public static function issues(): array
    {
        $issues = [];
        $add = function (string $message) use (&$issues): void {
            $issues[] = ['type' => 'warning', 'message' => $message];
        };
        foreach (Article::where('is_published', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->get() as $article) {
            $missing = [];
            foreach (['author_name' => 'الكاتب', 'medical_reviewer' => 'المراجع الطبي', 'reviewed_at' => 'تاريخ المراجعة', 'meta_description' => 'وصف البحث'] as $field => $label) {
                if (! $article->$field) {
                    $missing[] = $label;
                }
            }
            if (! $article->sources) {
                $missing[] = 'المصادر';
            }
            if ($article->reviewed_at?->isFuture()) {
                $missing[] = 'تصحيح تاريخ المراجعة المستقبلي';
            }
            if ($missing) {
                $add('مقال «'.$article->title.'»: يحتاج '.implode('، ', $missing).'.');
            }
        }
        foreach (ClinicBranch::where('is_active', true)->get() as $branch) {
            $missing = [];
            foreach (['address' => 'العنوان', 'phone' => 'الهاتف', 'google_maps_url' => 'رابط الخريطة', 'meta_description' => 'وصف البحث'] as $field => $label) {
                if (trim($branch->$field ?? '') === '') {
                    $missing[] = $label;
                }
            }
            if ($missing) {
                $add('فرع «'.$branch->name.'»: يحتاج '.implode('، ', $missing).'.');
            }
        }
        $profile = SitePage::where('page_key', 'about')->first()?->doctor_profile;
        if (empty($profile['headline']) || empty($profile['qualifications'])) {
            $add('راجعي الصفة المهنية والمؤهلات الفعلية في محرر صفحة عن الدكتور.');
        }
        $incompleteVideos = Video::where('is_active', true)->get()->filter(fn ($video) => ! $video->uploaded_at || $video->uploaded_at->isFuture() || ! ($video->image_path || $video->hasMedia('video-thumbnail')))->count();
        if ($incompleteVideos) {
            $add('يوجد '.$incompleteVideos.' فيديوهات تحتاج صورة أو تاريخ نشر فعلي قبل تضمينها في البيانات المنظمة.');
        }

        return $issues;
    }
}
