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
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex');
    }

    public function test_success_returns_private_confirmation_url_and_consumes_boolean_flash(): void
    {
        $branch = ClinicBranch::first();
        $branch->update(['booking_schedule_enabled' => false]);
        $response = $this->postJson('/bookings', [
            'patient_name' => 'مريضة اختبار', 'patient_phone' => '01012345678',
            'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(),
            'service_id' => Service::first()->id, 'clinic_branch_id' => $branch->id,
        ])->assertOk()->assertJsonPath('confirmation_url', url('/booking-confirmation'));
        $this->assertIsBool(session('booking_confirmation_stored'));
        $page = $this->get($response->json('confirmation_url'))->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet, noimageindex')
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('طلبكِ جاهز للإرسال')->assertSee('فتح واتساب وحده لا يرسل الرسالة تلقائيًا')
            ->assertDontSee('01012345678')->assertDontSee('مريضة اختبار');
        $this->assertStringContainsString('no-store', $page->headers->get('Cache-Control'));
        $this->assertNull(session('booking_confirmation_stored'));
        $this->get('/booking-confirmation')->assertRedirect('/appointment');
    }

    public function test_handoff_only_copy_does_not_claim_request_was_stored(): void
    {
        $this->withSession(['booking_confirmation_stored' => false])->get('/booking-confirmation')
            ->assertOk()->assertSee('لم يُسجل طلب موعد على الموقع')->assertDontSee('تم تسجيل طلبكِ للمراجعة');
        $this->withSession(['booking_confirmation_stored' => true])->get('/booking-confirmation')
            ->assertOk()->assertSee('تم تسجيل طلبكِ للمراجعة')->assertSee('الموعد ما زال قيد التأكيد');
    }

    public function test_validation_failure_does_not_unlock_confirmation_page(): void
    {
        $this->postJson('/bookings', [])->assertUnprocessable();
        $this->get('/booking-confirmation')->assertRedirect('/appointment');
    }
}
