<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_direct_visit_redirects_without_claiming_success(): void
    {
        $this->get('/booking-confirmation')->assertRedirect('/appointment')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_success_returns_private_confirmation_url_and_consumes_boolean_flash(): void
    {
        $branch = ClinicBranch::first();
        $branch->update(['booking_schedule_enabled' => false, 'phone' => '01012349876']);
        $response = $this->postJson('/bookings', [
            'patient_name' => 'مريضة اختبار', 'patient_phone' => '01012345678',
            'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(),
            'service_id' => Service::first()->id, 'clinic_branch_id' => $branch->id,
        ])->assertOk()->assertJsonPath('confirmation_url', url('/booking-confirmation'));
        $this->assertIsBool(session('booking_confirmation_stored'));
        $page = $this->get($response->json('confirmation_url'))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('تم استلام طلب الحجز')->assertSee('فريق العيادة هيتواصل معاك لتأكيد الموعد.')
            ->assertSee('https://wa.me/201012349876?text=', false)
            ->assertDontSee('مريضة اختبار');
        $this->assertStringContainsString('no-store', $page->headers->get('Cache-Control'));
        $this->assertNull(session('booking_confirmation_stored'));
        $this->get('/booking-confirmation')->assertRedirect('/appointment');
    }

    public function test_confirmation_copy_is_concise_for_both_booking_modes(): void
    {
        foreach ([false, true] as $stored) {
            $this->withSession(['booking_confirmation_stored' => $stored])->get('/booking-confirmation')
                ->assertOk()->assertSee('تم استلام طلب الحجز')->assertSee('فريق العيادة هيتواصل معاك لتأكيد الموعد.')
                ->assertDontSee('فتح واتساب وحده')->assertDontSee('تم تأكيد موعدكِ');
        }
    }

    public function test_fertility_answers_are_saved_only_for_fertility_service(): void
    {
        $branch = ClinicBranch::first();
        $branch->update(['booking_schedule_enabled' => false, 'phone' => null]);
        $fertility = Service::where('slug', 'service-2')->firstOrFail();
        $payload = ['patient_name' => 'اختبار', 'patient_phone' => '01012345678', 'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(), 'service_id' => $fertility->id, 'clinic_branch_id' => $branch->id];
        $this->postJson('/bookings', $payload + ['previous_pregnancy' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors('previous_pregnancy');
        $this->postJson('/bookings', $payload + ['previous_pregnancy' => 'yes', 'pregnancy_related_surgery' => 'no'])->assertOk()->assertJsonMissingPath('whatsapp_url');
        $this->assertDatabaseHas('website_booking_requests', ['admin_notes' => 'إجابات المريضة عند الحجز: حمل سابق: نعم، عملية مرتبطة بالحمل: لا']);
        $this->get('/booking-confirmation')->assertOk()->assertDontSee('تواصل مع الفرع على واتساب');
        $other = Service::where('slug', '!=', 'service-2')->firstOrFail();
        $this->postJson('/bookings', array_replace($payload, ['service_id' => $other->id, 'previous_pregnancy' => 'bad']))->assertOk();
        $this->assertDatabaseHas('website_booking_requests', ['service_id' => $other->id, 'admin_notes' => null]);
    }

    public function test_validation_failure_does_not_unlock_confirmation_page(): void
    {
        $this->postJson('/bookings', [])->assertUnprocessable();
        $this->get('/booking-confirmation')->assertRedirect('/appointment');
    }
}
