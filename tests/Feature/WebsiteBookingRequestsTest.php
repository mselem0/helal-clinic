<?php

namespace Tests\Feature;

use App\Filament\Resources\WebsiteBookingRequestResource;
use App\Filament\Resources\WebsiteBookingRequestResource\Pages\EditWebsiteBookingRequest;
use App\Filament\Resources\WebsiteBookingRequestResource\Pages\ListWebsiteBookingRequests;
use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteBookingRequest;
use App\Services\WebsiteBookingRequestExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
#[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
class WebsiteBookingRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        ClinicBranch::query()->update(['booking_schedule_enabled' => false]);
    }

    private function payload(): array
    {
        return ['patient_name' => '=اختبار', 'patient_phone' => '01012345678', 'booking_date' => now('Africa/Cairo')->addDays(2)->toDateString(), 'service_id' => Service::first()->id, 'clinic_branch_id' => ClinicBranch::first()->id, 'request_key' => (string) Str::uuid()];
    }

    private function admin(string $role = 'super_admin'): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_public_requests_are_stored_with_snapshots_and_retries_do_not_duplicate(): void
    {
        $payload = $this->payload();
        $this->postJson('/bookings', $payload)->assertOk()->assertJsonPath('stored', true);
        $this->postJson('/bookings', $payload)->assertOk();
        $this->assertDatabaseCount('website_booking_requests', 1);
        $record = WebsiteBookingRequest::first();
        $this->assertSame(Service::first()->name, $record->service_name);
        $this->assertSame('website', $record->source_host);
        $this->postJson('/bookings', array_replace($payload, ['patient_phone' => '01112345678']))->assertConflict()->assertDontSee($payload['patient_phone']);
        $this->assertDatabaseCount('website_booking_requests', 1);
    }

    public function test_international_phones_are_normalized_and_saved(): void
    {
        foreach (['+966 50 123 4567' => '966501234567', '00971-50-123-4567' => '971501234567', '٠١٠١٢٣٤٥٦٧٨' => '01012345678'] as $input => $expected) {
            $payload = array_replace($this->payload(), ['patient_phone' => $input]);
            $response = $this->postJson('/bookings', $payload)->assertOk();
            $this->assertDatabaseHas('website_booking_requests', ['request_key' => $payload['request_key'], 'patient_phone' => $expected]);
            $this->assertStringContainsString($expected, rawurldecode($response->json('whatsapp_url')));
        }
        foreach (['123', '+1234567890123456', 'phone1234567', '++966501234567'] as $invalid) {
            $this->postJson('/bookings', array_replace($this->payload(), ['patient_phone' => $invalid]))->assertUnprocessable()->assertJsonValidationErrors('patient_phone');
        }
    }

    public function test_storage_failure_does_not_flash_success_or_return_a_handoff(): void
    {
        WebsiteBookingRequest::creating(fn () => throw new \RuntimeException('storage unavailable'));
        try {
            $this->postJson('/bookings', $this->payload())->assertStatus(500)->assertJsonMissingPath('whatsapp_url');
            $this->assertNull(session('booking_confirmation_stored'));
        } finally {
            WebsiteBookingRequest::flushEventListeners();
        }
    }

    public function test_admin_filters_and_edit_only_followup_fields(): void
    {
        $this->admin();
        $this->postJson('/bookings', $this->payload())->assertOk();
        $record = WebsiteBookingRequest::first();
        Livewire::test(ListWebsiteBookingRequests::class)->assertCanSeeTableRecords([$record])->filterTable('status', 'confirmed')->assertCanNotSeeTableRecords([$record]);
        Livewire::test(ListWebsiteBookingRequests::class)->filterTable('booking_date', ['from' => now()->addDays(10)->toDateString()])->assertCanNotSeeTableRecords([$record]);
        Livewire::test(ListWebsiteBookingRequests::class)->filterTable('clinic_branch_id', $record->clinic_branch_id)->assertCanSeeTableRecords([$record]);
        Livewire::test(EditWebsiteBookingRequest::class, ['record' => $record->id])->fillForm(['status' => 'contacted', 'admin_notes' => 'تم الاتصال', 'patient_name' => 'تغيير غير مسموح'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('contacted', $record->fresh()->status);
        $this->assertSame('=اختبار', $record->fresh()->patient_name);
        $this->assertFalse(WebsiteBookingRequestResource::canCreate());
        $this->assertFalse(WebsiteBookingRequestResource::canDelete($record));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_export_is_a_real_workbook_with_safe_strings_and_filtered_rows(): void
    {
        $this->admin();
        $this->postJson('/bookings', $this->payload())->assertOk();
        $record = WebsiteBookingRequest::first();
        $record->update(['admin_notes' => '=HYPERLINK("https://example.test")']);
        $book = app(WebsiteBookingRequestExport::class)->spreadsheet(WebsiteBookingRequest::query()->whereKey($record->id));
        $path = tempnam(sys_get_temp_dir(), 'booking-xlsx-');
        try {
            (new Xlsx($book))->save($path);
            $loaded = IOFactory::load($path);
            $sheet = $loaded->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertSame('01012345678', $sheet->getCell('C2')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B2')->getDataType());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('H2')->getDataType());
            $this->assertSame('=HYPERLINK("https://example.test")', $sheet->getCell('H2')->getValue());
            $loaded->disconnectWorksheets();
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
    }

    public function test_guests_and_non_followup_users_cannot_read_or_export_requests(): void
    {
        $this->assertFalse(WebsiteBookingRequestResource::canViewAny());
        $this->admin('content_manager');
        $this->assertFalse(WebsiteBookingRequestResource::canViewAny());
        Livewire::test(ListWebsiteBookingRequests::class)->assertForbidden();
        $this->expectException(HttpException::class);
        app(WebsiteBookingRequestExport::class)->spreadsheet(WebsiteBookingRequest::query());
    }
}
