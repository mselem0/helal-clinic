<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_weekly_hours_and_reject_invalid_ranges(): void
    {
        $this->seed();
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        $branch = ClinicBranch::first();
        $component = \Livewire\Livewire::test(\App\Filament\Resources\ClinicBranchResource\Pages\EditClinicBranch::class, ['record' => $branch->id]);
        $component->fillForm(['booking_schedule_enabled' => true, 'booking_time_enabled' => false, 'weekly_schedule' => ['0' => ['open' => true, 'from' => '17:00', 'to' => '21:00']]])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($branch->fresh()->booking_schedule_enabled);
        $this->assertFalse($branch->fresh()->booking_time_enabled);
        $component->fillForm(['booking_time_enabled' => true])->call('save')->assertHasNoFormErrors();
        $this->assertTrue($branch->fresh()->booking_time_enabled);
        $this->assertSame('17:00', $branch->fresh()->weekly_schedule[0]['from']);
        $component->fillForm(['schedule_exceptions' => [['date' => '2026-12-25', 'closed' => true]]])->call('save')->assertHasNoFormErrors();
        $this->assertTrue(array_values($branch->fresh()->schedule_exceptions)[0]['closed']);
        $component->fillForm(['schedule_exceptions' => [['date' => '2026-12-25', 'closed' => true], ['date' => '2026-12-25', 'closed' => true]]])->call('save')->assertHasFormErrors();
        $component->fillForm(['schedule_exceptions' => []]);
        $component->fillForm(['weekly_schedule' => ['0' => ['open' => true, 'from' => '21:00', 'to' => '17:00']]])->call('save')->assertHasFormErrors(['weekly_schedule.0.to']);
    }

    public function test_closed_days_are_rejected_before_any_booking_write_and_exceptions_override_weekly_hours(): void
    {
        Http::preventStrayRequests();
        $this->seed();
        $branch = ClinicBranch::first();
        $day = now('Africa/Cairo')->addDays(2)->toDateString();
        $branch->update(['booking_schedule_enabled' => true, 'weekly_schedule' => [], 'schedule_exceptions' => []]);
        $payload = ['patient_name' => 'اختبار', 'patient_phone' => '01012345678', 'booking_date' => $day, 'service_id' => Service::first()->id, 'clinic_branch_id' => $branch->id];
        $this->postJson('/bookings', $payload)->assertUnprocessable()->assertJsonValidationErrors(['booking_date']);
        $this->assertDatabaseCount('bookings', 0);
        $branch->update(['schedule_exceptions' => [['date' => $day, 'closed' => false, 'from' => '17:00', 'to' => '21:00']]]);
        $payload['booking_time'] = '18:30';
        $result = $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('status', 'pending')->json();
        $whatsapp = rawurldecode(session('booking_confirmation_branch_whatsapp'));
        $this->assertStringContainsString('الساعة المختارة: 18:30 بتوقيت القاهرة', $whatsapp);
        $this->assertStringNotContainsString('مواعيد الزيارة المعروضة', $whatsapp);
    }
}
