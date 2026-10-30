<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\WebsiteBookingRequest;
use App\Support\BookingTime;
use App\Support\BookingVisitDetails;
use App\Support\BranchSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingTimePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 20:00:00', 'Africa/Cairo'));
    }

    private function payload(string $time = '21:30', string $date = '2026-11-02'): array
    {
        $branch = ClinicBranch::first();
        $branch->update(['booking_schedule_enabled' => true, 'booking_time_enabled' => true, 'weekly_schedule' => array_fill(0, 7, ['open' => true, 'from' => '21:00', 'to' => '23:00'])]);
        return ['patient_name' => 'Synthetic', 'patient_phone' => '01012345678', 'booking_date' => $date, 'booking_time' => $time, 'service_id' => Service::first()->id, 'clinic_branch_id' => $branch->id, 'request_key' => (string) Str::uuid(), 'visit_type' => 'first_visit', 'visit_window' => BookingVisitDetails::window($branch->fresh(), BranchSchedule::forDate($branch->fresh(), $date))];
    }

    public function test_half_hour_slots_are_offered_and_can_be_shared_by_multiple_patients(): void
    {
        $payload = $this->payload();
        $branch = ClinicBranch::find($payload['clinic_branch_id']);
        $this->assertSame(['21:00', '21:30', '22:00', '22:30'], BookingTime::slots($branch, $payload['booking_date']));
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertSame('21:30', WebsiteBookingRequest::first()->booking_time);
        $whatsapp = rawurldecode(session('booking_confirmation_branch_whatsapp'));
        $this->assertStringContainsString('الساعة المختارة: 21:30 بتوقيت القاهرة', $whatsapp);
        $this->assertStringNotContainsString('مواعيد الزيارة المعروضة', $whatsapp);
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertDatabaseCount('website_booking_requests', 1);
        $this->postJson('/bookings', array_replace($payload, ['request_key' => (string) Str::uuid(), 'patient_name' => 'Another patient']))->assertOk();
        $this->assertDatabaseCount('website_booking_requests', 2);
        $this->postJson('/bookings', array_replace($payload, ['booking_time' => '22:00']))->assertConflict();
    }

    public function test_off_slot_malformed_and_out_of_range_times_are_rejected(): void
    {
        $payload = $this->payload();
        foreach ([null, '20:59', '23:00', '21:17', '21:15', '09:00 PM', '21:17:30'] as $time) {
            $this->postJson('/bookings', array_replace($payload, ['booking_time' => $time]))->assertUnprocessable()->assertJsonValidationErrors('booking_time');
        }
        $this->assertDatabaseCount('website_booking_requests', 0);
    }

    public function test_exception_window_midnight_and_disabled_selection_are_respected(): void
    {
        $payload = $this->payload('23:30');
        $branch = ClinicBranch::find($payload['clinic_branch_id']);
        $branch->update(['schedule_exceptions' => [['date' => $payload['booking_date'], 'closed' => false, 'from' => '22:00', 'to' => '00:00']]]);
        $this->assertSame(['22:00', '22:30', '23:00', '23:30'], BookingTime::slots($branch->fresh(), $payload['booking_date']));
        $payload['visit_window'] = BookingVisitDetails::window($branch->fresh(), BranchSchedule::forDate($branch->fresh(), $payload['booking_date']));
        $this->postJson('/bookings', $payload)->assertOk();
        $payload['request_key'] = (string) Str::uuid();
        $payload['booking_time'] = '21:30';
        $this->postJson('/bookings', $payload)->assertUnprocessable()->assertJsonValidationErrors('booking_time');
        $branch->update(['booking_time_enabled' => false]);
        $this->postJson('/bookings', $payload)->assertUnprocessable()->assertJsonValidationErrors('booking_time');
        unset($payload['booking_time']);
        $this->postJson('/bookings', $payload)->assertOk();
    }

    public function test_past_time_in_cairo_and_closing_exception_are_rejected(): void
    {
        $payload = $this->payload('21:00', '2026-11-01');
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-01 21:30:00', 'Africa/Cairo'));
        $this->postJson('/bookings', $payload)->assertUnprocessable()->assertJsonValidationErrors('booking_time');
        $branch = ClinicBranch::find($payload['clinic_branch_id']);
        $branch->update(['schedule_exceptions' => [['date' => $payload['booking_date'], 'closed' => true]]]);
        $this->postJson('/bookings', $payload)->assertUnprocessable()->assertJsonValidationErrors('booking_date');
    }
}
