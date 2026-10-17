<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\ThemeRevision;
use App\Services\ThemeDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DoctorProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_is_managed_through_drafts_preview_publish_and_restore(): void
    {
        $this->seed();
        $page = SitePage::where('page_key', 'about')->firstOrFail();
        $original = $page->doctor_profile;
        $this->get('/about-us')->assertOk()->assertSee('المؤهلات العلمية')->assertSee('الجمعية المصرية للخصوبة والعقم');
        $draft = $original;
        $draft['memberships'][] = ['title' => 'عضوية اختبار غير منشورة'];
        $page->update(['draft_data' => ['doctor_profile' => $draft]]);
        $this->get('/about-us')->assertDontSee('عضوية اختبار غير منشورة');
        $preview = URL::temporarySignedRoute('site.preview', now()->addMinutes(10), ['page' => $page->id, 'preview' => 1]);
        $this->get($preview)->assertOk()->assertSee('عضوية اختبار غير منشورة');
        app(ThemeDraftService::class)->publish();
        $this->get('/about-us')->assertSee('عضوية اختبار غير منشورة');
        app(ThemeDraftService::class)->restoreRevision(ThemeRevision::firstOrFail()->id);
        $this->get('/about-us')->assertDontSee('عضوية اختبار غير منشورة');
        $this->assertSame($original, $page->fresh()->doctor_profile);
    }
}
