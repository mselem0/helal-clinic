<?php

namespace Tests\Feature;

use App\Models\ClinicBranch;
use App\Models\Service;
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

    public function test_fertility_answers_are_added_to_whatsapp_handoff(): void
    {
        $payload = $this->booking('service-2') + [
            'previous_pregnancy' => 'yes',
            'pregnancy_related_surgery' => 'no',
        ];

        $response = $this->postJson('/bookings', $payload)->assertOk();
        $message = rawurldecode(parse_url($response->json('whatsapp_url'), PHP_URL_QUERY));

        $this->assertStringContainsString('حمل سابق: نعم.', $message);
        $this->assertStringContainsString('عملية مرتبطة بالحمل: لا.', $message);
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

    public function test_fertility_answers_are_optional_for_older_requests(): void
    {
        $response = $this->postJson('/bookings', $this->booking('service-2'))->assertOk();
        $message = rawurldecode(parse_url($response->json('whatsapp_url'), PHP_URL_QUERY));

        $this->assertStringNotContainsString('حمل سابق:', $message);
        $this->assertStringNotContainsString('عملية مرتبطة بالحمل:', $message);
    }

    public function test_fertility_answers_accept_only_yes_or_no(): void
    {
        $this->postJson('/bookings', $this->booking('service-2') + [
            'previous_pregnancy' => 'maybe',
        ])->assertUnprocessable()->assertJsonValidationErrors(['previous_pregnancy']);
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
