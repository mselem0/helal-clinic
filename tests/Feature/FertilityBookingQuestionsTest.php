<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Support\ClinicFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FertilityBookingQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed();
    }

    public function test_fertility_service_booking_is_rejected_without_creating_a_record(): void
    {
        $payload = $this->booking('service-2') + [
            'previous_pregnancy' => 'yes',
            'pregnancy_related_surgery' => 'no',
        ];

        $this->postJson('/bookings', $payload)->assertNotFound();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_unrelated_service_discards_unrecognized_fertility_answers(): void
    {
        $response = $this->postJson('/bookings', $this->booking('service-1') + [
            'previous_pregnancy' => 'free text must be ignored',
            'pregnancy_related_surgery' => ['unexpected'],
        ])->assertOk();

        $message = rawurldecode(parse_url($response->json('whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringNotContainsString('حمل سابق:', $message);
        $this->assertStringNotContainsString('عملية مرتبطة بالحمل:', $message);
    }

    public function test_fertility_service_booking_is_rejected_even_without_optional_answers(): void
    {
        $this->postJson('/bookings', $this->booking('service-2'))->assertNotFound();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_ordinary_service_booking_still_works_without_fertility_answers(): void
    {
        $response = $this->postJson('/bookings', $this->booking('service-1') + [
            'previous_pregnancy' => 'maybe',
        ])->assertOk()->assertJsonPath('status', 'pending');
        $message = rawurldecode(parse_url($response->json('whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringNotContainsString('حمل سابق:', $message);
        $this->assertDatabaseCount('bookings', ClinicFeatures::enabled('Booking') ? 1 : 0);
    }

    private function booking(string $serviceSlug): array
    {
        return [
            'patient_name' => 'اسم للاختبار',
            'patient_phone' => '01012345678',
            'booking_date' => now('Africa/Cairo')->next('Sunday')->format('Y-m-d'),
            'service_id' => Service::where('slug', $serviceSlug)->value('id'),
            'clinic_branch_id' => ClinicBranch::firstOrFail()->id,
        ];
    }
}
