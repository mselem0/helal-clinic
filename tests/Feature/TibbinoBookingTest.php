<?php

namespace Tests\Feature;

use App\Jobs\SyncTibbinoBookingRequest;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\WebsiteBookingRequest;
use App\Services\TibbinoBooking;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TibbinoBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tibbino.enabled' => true, 'services.tibbino.url' => 'http://tibbino.test', 'services.tibbino.token' => 'test-only-secret']);
        Http::preventStrayRequests();
        $this->seed();
    }

    private function payload(): array
    {
        $branch = ClinicBranch::firstOrFail();
        $branch->update(['booking_schedule_enabled' => false, 'phone' => '201001234567']);

        return ['request_key' => (string) Str::uuid(), 'patient_name' => 'اختبار', 'patient_phone' => '01012345678', 'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(), 'service_id' => Service::firstOrFail()->id, 'clinic_branch_id' => $branch->id];
    }

    private function remoteSuccess(array $payload, int $status = 201): PromiseInterface
    {
        return Http::response(['data' => ['request_id' => 42, 'status' => 'pending', 'date' => $payload['booking_date'], 'time' => null]], $status);
    }

    public function test_original_form_is_used_even_when_integration_is_enabled(): void
    {
        Http::fake();
        $this->get('/appointment')->assertOk()->assertSee('id="booking-form"', false)
            ->assertSee('id="clinic"', false)->assertSee('id="service"', false)
            ->assertDontSee('tibbino-booking-form')->assertDontSee('test-only-secret')->assertDontSee('id="doctor"', false);
        Http::assertNothingSent();
    }

    public function test_success_mirrors_pending_request_and_preserves_whatsapp_and_idempotency(): void
    {
        $payload = $this->payload();
        Http::fake(['*/booking-requests' => $this->remoteSuccess($payload)]);
        $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('stored', true)
            ->assertJsonPath('status', 'pending')->assertJsonStructure(['whatsapp_url', 'confirmation_url'])->assertDontSee('test-only-secret');
        $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('stored', true);
        $this->postJson('/bookings', array_replace($payload, ['patient_name' => 'اسم آخر']))->assertConflict();
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($payload): bool {
            $this->assertSame('http://tibbino.test/api/v1/public/booking-requests', $request->url());
            $this->assertSame(['request_key', 'patient_name', 'phone', 'date', 'service_name', 'branch_name', 'source_host'], array_keys($request->data()));
            $this->assertSame(Service::findOrFail($payload['service_id'])->name, $request['service_name']);
            $this->assertSame(ClinicBranch::findOrFail($payload['clinic_branch_id'])->name, $request['branch_name']);

            return $request->hasHeader('Authorization', 'Bearer test-only-secret') && $request['request_key'] === $payload['request_key'];
        });
        $this->assertDatabaseCount('website_booking_requests', 1);
        $this->assertDatabaseHas('website_booking_requests', ['request_key' => $payload['request_key'], 'remote_request_id' => 42, 'integration_state' => 'synced', 'status' => 'new', 'booking_time' => null, 'doctor_id' => null]);
    }

    public function test_timeout_does_not_rollback_local_request_and_retry_uses_same_key(): void
    {
        $payload = $this->payload();
        Http::fake(['*/booking-requests' => Http::sequence()->pushFailedConnection()->pushResponse($this->remoteSuccess($payload, 200))]);
        $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('stored', true)->assertJsonStructure(['whatsapp_url']);
        $record = WebsiteBookingRequest::firstOrFail();
        $this->assertSame('failed', $record->integration_state);
        $this->assertSame(1, $record->sync_attempts);
        $this->assertNotNull($record->next_sync_attempt_at);
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        $this->assertSame(1, $record->fresh()->sync_attempts);
        $this->travel(61)->seconds();
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        $this->assertSame('synced', $record->fresh()->integration_state);
        $this->assertSame(2, $record->fresh()->sync_attempts);
        Http::assertSent(fn ($request) => $request['request_key'] === $payload['request_key']);
        $this->assertDatabaseCount('website_booking_requests', 1);
    }

    public function test_retries_are_bounded_and_do_not_run_before_backoff(): void
    {
        Http::fake(['*/booking-requests' => Http::response([], 503)]);
        $this->postJson('/bookings', $this->payload())->assertOk();
        $record = WebsiteBookingRequest::firstOrFail();
        $this->assertSame(60, (int) $record->last_sync_attempt_at->diffInSeconds($record->next_sync_attempt_at));
        for ($i = 0; $i < 6; $i++) {
            $this->travel(20)->minutes();
            $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        }
        Http::assertSentCount(TibbinoBooking::MAX_ATTEMPTS);
        $this->assertSame(TibbinoBooking::MAX_ATTEMPTS, $record->fresh()->sync_attempts);
        $this->assertSame('failed', $record->fresh()->integration_state);
        $this->assertNull($record->fresh()->next_sync_attempt_at);
    }

    public function test_conflict_is_terminal_but_local_request_and_whatsapp_are_preserved(): void
    {
        Http::fake(['*/booking-requests' => Http::response(['message' => 'secret response must not be stored'], 409)]);
        $this->postJson('/bookings', $this->payload())->assertOk()->assertJsonPath('stored', true)->assertJsonStructure(['whatsapp_url']);
        $this->travel(20)->minutes();
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertDatabaseHas('website_booking_requests', ['integration_state' => 'failed', 'last_sync_error' => 'HTTP 409', 'next_sync_attempt_at' => null]);
    }

    public function test_disabled_or_missing_credentials_never_calls_upstream_but_saves_pending(): void
    {
        config(['services.tibbino.enabled' => false]);
        Http::fake();
        $payload = $this->payload();
        $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('stored', true)->assertJsonStructure(['whatsapp_url']);
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        config(['services.tibbino.enabled' => true, 'services.tibbino.token' => null]);
        $this->postJson('/bookings', array_replace($payload, ['request_key' => (string) Str::uuid()]))->assertOk();
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertSame(2, WebsiteBookingRequest::where('integration_state', 'pending')->where('sync_attempts', 0)->count());
    }

    public function test_scheduler_recovers_a_request_when_queue_dispatch_is_unavailable(): void
    {
        Bus::fake([SyncTibbinoBookingRequest::class]);
        $payload = $this->payload();
        unset($payload['request_key']);
        Http::fake(['*/booking-requests' => $this->remoteSuccess($payload)]);
        $this->postJson('/bookings', $payload)->assertOk();
        $record = WebsiteBookingRequest::firstOrFail();
        $this->assertTrue(Str::isUuid($record->request_key));
        Bus::assertDispatched(SyncTibbinoBookingRequest::class, fn ($job) => $job->requestId === $record->id);
        Http::assertNothingSent();
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        $this->assertSame('synced', $record->fresh()->integration_state);
    }

    public function test_live_lease_blocks_duplicate_sender_and_stale_lease_is_recovered(): void
    {
        Bus::fake([SyncTibbinoBookingRequest::class]);
        $payload = $this->payload();
        Http::fake(['*/booking-requests' => $this->remoteSuccess($payload)]);
        $this->postJson('/bookings', $payload)->assertOk();
        $record = WebsiteBookingRequest::firstOrFail();
        $record->update(['integration_state' => 'syncing', 'sync_attempts' => 1, 'last_sync_attempt_at' => now()]);
        app(TibbinoBooking::class)->sync($record->id);
        Http::assertNothingSent();
        $this->travel(61)->seconds();
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame('synced', $record->fresh()->integration_state);
    }

    public function test_malformed_success_does_not_mark_request_synced(): void
    {
        Http::fake(['*/booking-requests' => Http::response(['data' => ['request_id' => 42, 'status' => 'confirmed', 'time' => '14:00']], 201)]);
        $this->postJson('/bookings', $this->payload())->assertOk()->assertJsonPath('stored', true);
        $this->assertDatabaseHas('website_booking_requests', ['integration_state' => 'failed', 'remote_request_id' => null]);
    }

    public function test_interrupted_final_attempt_is_flagged_for_review_without_sending_again(): void
    {
        Bus::fake([SyncTibbinoBookingRequest::class]);
        Http::fake();
        $this->postJson('/bookings', $this->payload())->assertOk();
        $record = WebsiteBookingRequest::firstOrFail();
        $record->update(['integration_state' => 'syncing', 'sync_attempts' => TibbinoBooking::MAX_ATTEMPTS, 'last_sync_attempt_at' => now()->subMinutes(2)]);
        $this->artisan('tibbino:sync-booking-requests')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertSame('failed', $record->fresh()->integration_state);
        $this->assertNull($record->fresh()->next_sync_attempt_at);
    }
}
