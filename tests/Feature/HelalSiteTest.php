<?php

namespace Tests\Feature;

use App\Helpers\SeoHelper;
use App\Models\ClinicBranch;
use App\Models\PageSection;
use App\Models\SeoPage;
use App\Models\Service;
use App\Support\ClinicFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HelalSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_homepage_and_both_signed_theme_previews_render(): void
    {
        $this->get('/')->assertOk()->assertSee('هلال')->assertDontSee('محمد رجب')->assertSee('helal_classic')
            ->assertSee('اختاري اليوم المناسب')
            ->assertSee('حددي يومًا مفضلًا، ويؤكد فريق العيادة الوقت معكِ.');
        foreach (['helal_classic', 'helal_modern'] as $theme) {
            $this->get(URL::temporarySignedRoute('home', now()->addMinutes(5), ['preview' => 1, 'theme' => $theme]))->assertOk()->assertSee($theme)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
        $this->get('/?preview=1&theme=helal_modern')->assertForbidden();
    }

    public function test_basic_booking_prepares_whatsapp_without_claiming_storage(): void
    {
        $this->postJson('/bookings', $this->booking())->assertOk()->assertJson(['stored' => true, 'status' => 'pending'])->assertJsonPath('whatsapp_url', fn ($url) => str_starts_with($url, 'https://wa.me/201509011313?text='));
        $this->assertDatabaseCount('bookings', ClinicFeatures::enabled('Booking') ? 1 : 0);
    }

    public function test_booking_rejects_past_dates_bad_phone_and_inactive_branch(): void
    {
        $this->postJson('/bookings', array_replace($this->booking(), ['booking_date' => '2000-01-01', 'patient_phone' => 'bad']))->assertUnprocessable()->assertJsonValidationErrors(['booking_date', 'patient_phone']);
        ClinicBranch::query()->update(['is_active' => false]);
        $this->postJson('/bookings', $this->booking())->assertNotFound();
    }

    public function test_disabled_module_routes_are_not_accessible(): void
    {
        if (ClinicFeatures::enabled('PatientCore')) {
            $this->get('/patient-auth')->assertOk();
        } else {
            $this->get('/patient-auth')->assertNotFound();
        }
        if (ClinicFeatures::enabled('Clinical')) {
            $this->get('/admin/prescriptions/1/print')->assertRedirect();
        } else {
            $this->get('/admin/prescriptions/1/print')->assertNotFound();
        }
    }

    public function test_internal_pages_render_without_old_branding(): void
    {
        $this->get('/services')->assertOk()->assertDontSee('محمد رجب');
        $this->get('/services/'.Service::first()->slug)->assertOk();
        $this->get('/blog')->assertOk();
    }

    public function test_seeder_does_not_overwrite_edited_content(): void
    {
        $section = PageSection::first();
        $section->update(['body' => '{"fields":[]}']);
        $this->seed();
        $this->assertSame('{"fields":[]}', $section->fresh()->body);
    }

    public function test_seo_settings_drive_metadata_and_sitemap_excludes_drafts(): void
    {
        SeoPage::where('page_key', 'home')->update(['meta_title' => 'عنوان SEO خاص بهلال']);
        SeoHelper::clearCache();
        $this->get('/')->assertOk()->assertSee('<title>عنوان SEO خاص بهلال</title>', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/services/service-1')->assertDontSee('/blog/article-1');
    }

    private function booking(): array
    {
        return ['patient_name' => 'اسم للاختبار', 'patient_phone' => '01012345678', 'booking_date' => now('Africa/Cairo')->next('Sunday')->format('Y-m-d'), 'service_id' => Service::first()->id, 'clinic_branch_id' => ClinicBranch::first()->id];
    }
}
