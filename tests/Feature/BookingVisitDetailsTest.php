<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\WebsiteBookingRequest;
use App\Support\BookingVisitDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingVisitDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['clinic-integrations.tibbino.enabled' => false]);
    }

    private function payload(): array
    {
        $branch = ClinicBranch::first();
        $branch->update(['booking_schedule_enabled' => false, 'working_hours' => 'من 5 إلى 9 مساءً']);
        return ['patient_name' => 'Synthetic', 'patient_phone' => '01012345678', 'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(), 'service_id' => Service::first()->id, 'clinic_branch_id' => $branch->id, 'request_key' => (string) Str::uuid(), 'visit_type' => 'follow_up', 'visit_window' => 'من 5 إلى 9 مساءً'];
    }

    public function test_details_are_saved_and_whatsapp_uses_the_same_snapshot(): void
    {
        $payload = $this->payload();
        $this->postJson('/bookings', $payload)->assertOk();
        $booking = WebsiteBookingRequest::firstOrFail();
        $this->assertSame('follow_up', $booking->visit_type);
        $this->assertSame($payload['visit_window'], $booking->visit_window);
        $this->assertNull($booking->visit_window_acknowledged_at);
        $url = rawurldecode(session('booking_confirmation_branch_whatsapp'));
        $this->assertStringContainsString('متابعة / استشارة', $url);
        $this->assertStringContainsString($payload['visit_window'], $url);
        $this->assertStringNotContainsString('أؤكد', $url);
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertDatabaseCount('website_booking_requests', 1);
        ClinicBranch::whereKey($payload['clinic_branch_id'])->update(['working_hours' => 'Changed later']);
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertSame($payload['visit_window'], $booking->fresh()->visit_window);
        $this->postJson('/bookings', array_replace($payload, ['visit_type' => 'first_visit']))->assertConflict();
    }

    public function test_stale_or_forged_window_is_rejected(): void
    {
        $payload = $this->payload();
        $this->postJson('/bookings', array_replace($payload, ['visit_window' => 'forged']))->assertUnprocessable();
        $this->postJson('/bookings', array_replace($payload, ['visit_type' => 'invalid']))->assertUnprocessable();
        $this->assertDatabaseCount('website_booking_requests', 0);
    }

    public function test_legacy_client_stores_no_visit_type(): void
    {
        $payload = $this->payload();
        unset($payload['visit_type'], $payload['visit_window']);
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertNull(WebsiteBookingRequest::firstOrFail()->visit_type);
    }
    public function test_first_visit_uses_the_weekly_window_with_midnight_close(): void
    {
        $payload = $this->payload();
        $branch = ClinicBranch::findOrFail($payload['clinic_branch_id']);
        $day = (new \DateTimeImmutable($payload['booking_date']))->format('w');
        $branch->update(['booking_schedule_enabled' => true, 'weekly_schedule' => [$day => ['open' => true, 'from' => '18:00', 'to' => '00:00']]]);
        $payload['visit_type'] = 'first_visit';
        $payload['booking_time'] = '21:30';
        $payload['visit_window'] = 'من 6:00 مساءً إلى 12 منتصف الليل';
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertSame('first_visit', WebsiteBookingRequest::first()->visit_type);
        $this->assertSame($payload['visit_window'], WebsiteBookingRequest::first()->visit_window);
    }

}
