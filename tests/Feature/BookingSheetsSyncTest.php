<?php

namespace Tests\Feature;

use App\Models\WebsiteBookingRequest;
use App\Models\WebsiteBookingSheetSync;
use App\Services\GoogleSheets\BookingSheetsSync;
use App\Services\GoogleSheets\SheetsGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FakeBookingSheetsGateway implements SheetsGateway
{
    public array $data = [];

    public bool $fail = false;

    public int $writes = 0;

    public int $reads = 0;

    public $onWrite = null;

    public $onRows = null;

    public int $rowReads = 0;

    public array $patches = [];

    public function serviceAccountEmail(): ?string
    {
        return 'test@example.test';
    }

    public function prepare(string $id, string $title, ?int $sheetId): array
    {
        $this->reads++;
        if ($this->fail) {
            throw new \RuntimeException('SECRET patient payload', 429);
        }
        if (! $this->data) {
            $this->data = [BookingSheetsSync::HEADERS];
        }

        return ['title' => 'Test', 'worksheet_title' => 'Dedicated', 'worksheet_id' => 123];
    }

    public function rows(string $id, string $title): array
    {
        $this->rowReads++;
        if ($this->onRows) {
            ($this->onRows)($this->rowReads);
        }

        return $this->data;
    }

    public function write(string $id, string $title, ?int $row, array $values): void
    {
        if ($this->fail) {
            throw new \RuntimeException('SECRET patient payload', 429);
        }
        $this->writes++;
        if ($this->onWrite) {
            $callback = $this->onWrite;
            $this->onWrite = null;
            $callback();
        } if ($row) {
            $this->data[$row - 1] = $values;
        } else {
            $this->data[] = $values;
        }
    }

    public function patch(string $id, string $title, int $row, array $cells): void
    {
        $this->patches[] = $cells;
        if ($this->fail) {
            throw new \RuntimeException('SECRET patient payload', 429);
        }
        $this->writes++;
        if ($this->onWrite) {
            $callback = $this->onWrite;
            $this->onWrite = null;
            $callback();
        }
        foreach ($cells as $column => $value) {
            $this->data[$row - 1][$column] = $value;
        }
    }
}
class BookingSheetsSyncTest extends TestCase
{
    private FakeBookingSheetsGateway $gateway;

    private BookingSheetsSync $sync;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('website_booking_sheet_changes');
        Schema::dropIfExists('website_booking_sheet_syncs');
        Schema::dropIfExists('google_sheets_connections');
        Schema::dropIfExists('website_booking_requests');
        (require database_path('migrations/2026_10_26_100000_create_website_booking_requests_table.php'))->up();
        (require database_path('migrations/2026_10_30_140000_add_visit_details_to_website_booking_requests.php'))->up();
        Schema::table('website_booking_requests', fn ($table) => $table->string('booking_time', 5)->nullable());
        (require database_path('migrations/2026_10_28_120000_create_google_sheets_sync_tables.php'))->up();
        (require database_path('migrations/2026_10_28_160000_create_website_booking_sheet_changes_table.php'))->up();
        $this->gateway = new FakeBookingSheetsGateway;
        $this->app->instance(SheetsGateway::class, $this->gateway);
        $this->sync = app(BookingSheetsSync::class);
    }

    private function booking(array $data = []): WebsiteBookingRequest
    {
        return WebsiteBookingRequest::create(array_merge(['patient_name' => '=IMPORTXML("bad")', 'patient_phone' => '01001234567', 'booking_date' => '2026-11-01', 'service_name' => 'Service', 'branch_name' => 'Branch', 'source_host' => 'helal.test', 'status' => 'new'], $data));
    }

    private function connect(): void
    {
        $this->sync->connect('https://docs.google.com/spreadsheets/d/abcdefghijklmnopqrstuvw/edit');
    }

    public function test_disconnected_booking_is_durable_pending_and_uuid_then_backfills(): void
    {
        $b = $this->booking();
        $this->assertNotEmpty($b->request_key);
        $this->assertSame('pending', $b->sheetSync->status);
        $this->connect();
        $this->assertSame(1, $this->sync->syncNow()['synced']);
        $this->assertSame('01001234567', $this->gateway->data[1][2]);
        $this->assertSame('=IMPORTXML("bad")', $this->gateway->data[1][1]);
        $this->assertSame('synced', $b->sheetSync()->first()->status);
    }

    public function test_after_commit_sync_failure_never_rolls_back_booking_and_retries(): void
    {
        $this->connect();
        $this->gateway->fail = true;
        DB::transaction(fn () => $this->booking());
        $this->assertSame(1, WebsiteBookingRequest::count());
        $state = WebsiteBookingSheetSync::first();
        $this->assertSame('failed', $state->status);
        $this->assertStringNotContainsString('SECRET', $state->last_error);
        $this->gateway->fail = false;
        $this->travel(61)->seconds();
        $this->assertSame(1, $this->sync->syncNow()['synced']);
        $this->assertCount(2, $this->gateway->data);
    }

    public function test_imports_only_allowed_fields_and_uses_keys_after_row_reorder(): void
    {
        $this->connect();
        $a = $this->booking();
        $b = $this->booking(['patient_name' => 'Second']);
        [$this->gateway->data[1],$this->gateway->data[2]] = [$this->gateway->data[2], $this->gateway->data[1]];
        $this->gateway->data[2][7] = 'confirmed';
        $this->gateway->data[2][8] = 'Called';
        $this->gateway->data[2][1] = 'Malicious patient edit';
        $this->sync->syncNow();
        $this->assertSame('confirmed', $a->fresh()->status);
        $this->assertSame('Called', $a->fresh()->admin_notes);
        $this->assertSame('new', $b->fresh()->status);
        $this->assertSame('Malicious patient edit', $a->fresh()->patient_name);
    }

    public function test_three_way_conflict_preserves_local_value_and_reviewable_remote_value(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['status' => 'contacted'])->saveQuietly();
        $this->gateway->data[1][7] = 'confirmed';
        $this->sync->syncNow();
        $this->assertSame('contacted', $b->fresh()->status);
        $this->assertSame('confirmed', $this->gateway->data[1][7]);
        $this->assertSame('confirmed', $b->sheetSync()->first()->conflicts['status']['sheet']);
        $this->sync->syncNow();
        $this->assertNotEmpty($b->sheetSync()->first()->conflicts);
    }

    public function test_deleted_rows_recreated_invalid_status_rejected_and_duplicate_keys_fail_closed(): void
    {
        $this->connect();
        $b = $this->booking();
        $this->gateway->data = [BookingSheetsSync::HEADERS];
        $this->sync->syncNow();
        $this->assertCount(2, $this->gateway->data);
        $this->gateway->data[1][7] = 'not-valid';
        $this->sync->syncNow();
        $this->assertSame('new', $b->fresh()->status);
        $this->assertSame('invalid_value', $b->sheetSync()->first()->conflicts['status']['reason']);
        $this->gateway->data[] = $this->gateway->data[1];
        $writes = $this->gateway->writes;
        $this->assertSame(1, $this->sync->syncNow()['failed']);
        $this->assertSame($writes, $this->gateway->writes);
    }

    public function test_historical_request_missing_key_is_backfilled(): void
    {
        $b = $this->booking();
        DB::table('website_booking_requests')->where('id', $b->id)->update(['request_key' => null]);
        $this->connect();
        $this->sync->syncNow();
        $this->assertNotEmpty($b->fresh()->request_key);
    }

    public function test_local_edit_during_outbound_write_remains_pending_and_next_sync_exports_it(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['admin_notes' => 'sent value'])->saveQuietly();
        $this->gateway->onWrite = fn () => WebsiteBookingRequest::whereKey($b->id)->update(['admin_notes' => 'newer local value']);
        $this->sync->syncNow();
        $this->assertSame('newer local value', $b->fresh()->admin_notes);
        $state = $b->sheetSync()->first();
        $this->assertSame('pending', $state->status);
        $this->assertSame('sent value', $state->snapshot['admin_notes']);
        $this->sync->syncNow();
        $this->assertSame('newer local value', $this->gateway->data[1][8]);
    }

    public function test_invalid_headers_fail_without_writes_or_local_changes(): void
    {
        $this->connect();
        $b = $this->booking();
        $this->gateway->data[0][0] = 'changed';
        $writes = $this->gateway->writes;
        $this->assertSame(1, $this->sync->syncNow()['failed']);
        $this->assertSame($writes, $this->gateway->writes);
        $this->assertSame('new', $b->fresh()->status);
    }

    public function test_failed_connection_test_does_not_replace_existing_connection(): void
    {
        $this->connect();
        $id = $this->sync->connection()->spreadsheet_id;
        $this->gateway->fail = true;
        try {
            $this->sync->connect('zyxwvutsrqponmlkjihgfedcba');
            $this->fail('Expected validation error');
        } catch (ValidationException) {
        }
        $this->assertSame($id, $this->sync->connection()->fresh()->spreadsheet_id);
    }

    public function test_bulk_write_budget_defers_remaining_requests_and_processes_them_next_run(): void
    {
        $this->connect();
        WebsiteBookingRequest::withoutEvents(function () {
            for ($i = 0; $i < 50; $i++) {
                $this->booking(['request_key' => (string) Str::uuid()]);
            }
        });
        $writes = $this->gateway->writes;
        $this->assertSame(45, $this->sync->syncNow()['synced']);
        $this->assertSame(45, $this->gateway->writes - $writes);
        $this->sync->syncNow();
        $this->assertCount(51, $this->gateway->data);
        $this->assertSame(50, WebsiteBookingSheetSync::where('status', 'synced')->count());
    }

    public function test_rate_limit_applies_global_cooldown_without_further_provider_requests(): void
    {
        $this->connect();
        $this->booking();
        $this->gateway->fail = true;
        $this->sync->syncNow();
        $reads = $this->gateway->reads;
        $this->assertTrue($this->sync->connection()->fresh()->retry_after->isFuture());
        $this->assertTrue($this->sync->syncNow()['deferred']);
        $this->assertSame($reads, $this->gateway->reads);
    }

    public function test_arabic_status_roundtrip_accepts_old_english_and_rejects_unknown_labels(): void
    {
        $this->connect();
        $b = $this->booking();
        $this->assertSame('جديد', $this->gateway->data[1][7]);
        $this->gateway->data[1][7] = 'مؤكد';
        $this->sync->syncNow();
        $this->assertSame('confirmed', $b->fresh()->status);
        $this->gateway->data[1][7] = 'cancelled';
        $this->sync->syncNow();
        $this->assertSame('cancelled', $b->fresh()->status);
        $this->assertSame('cancelled', $this->gateway->data[1][7]);
        $this->gateway->data[1][7] = 'حالة غير معروفة';
        $this->sync->syncNow();
        $this->assertSame('cancelled', $b->fresh()->status);
        $this->assertSame('invalid_value', $b->sheetSync()->first()->conflicts['status']['reason']);
    }

    public function test_disconnect_is_local_preserves_requests_key_and_sheet_then_reconnects_backfill(): void
    {
        $this->connect();
        $b = $this->booking();
        $key = $this->sync->connection()->installation_key;
        $sheet = $this->gateway->data;
        $reads = $this->gateway->reads;
        $writes = $this->gateway->writes;
        $this->sync->disconnect();
        $c = $this->sync->connection();
        $this->assertNull($c->spreadsheet_id);
        $this->assertNull($c->worksheet_id);
        $this->assertNull($c->last_synced_at);
        $this->assertSame($key, $c->installation_key);
        $this->assertSame(1, WebsiteBookingRequest::count());
        $this->assertSame($sheet, $this->gateway->data);
        $this->assertSame($reads, $this->gateway->reads);
        $this->assertSame($writes, $this->gateway->writes);
        $state = $b->sheetSync()->first();
        $this->assertSame('pending', $state->status);
        $this->assertNull($state->snapshot);
        $this->assertNull($state->retry_after);
        $this->sync->syncNow();
        $this->assertSame($reads, $this->gateway->reads);
        $this->gateway->data = [];
        $this->connect();
        $this->sync->syncNow();
        $this->assertSame($b->request_key, $this->gateway->data[1][0]);
    }

    public function test_patient_fields_import_with_trusted_ids_phone_normalization_and_serial_date(): void
    {
        foreach (['services', 'clinic_branches'] as $table) {
            if (! Schema::hasTable($table)) {
                Schema::create($table, function ($t) {
                    $t->id();
                    $t->string('name');
                });
            }
        }
        $sid = DB::table('services')->insertGetId(['name' => 'Registered service']);
        $bid = DB::table('clinic_branches')->insertGetId(['name' => 'Registered branch']);
        $this->connect();
        $b = $this->booking();
        $this->gateway->data[1][1] = 'اسم جديد';
        $this->gateway->data[1][2] = '+٢٠ (١٠) ١٢٣٤٥٦٧٨';
        $this->gateway->data[1][3] = 46328;
        $this->gateway->data[1][4] = 'Registered service';
        $this->gateway->data[1][5] = 'Registered branch';
        $this->gateway->data[1][8] = 'Sheet note';
        $this->sync->syncNow();
        $b->refresh();
        $this->assertSame('اسم جديد', $b->patient_name);
        $this->assertSame('201012345678', $b->patient_phone);
        $this->assertSame('2026-11-02', $b->booking_date->format('Y-m-d'));
        $this->assertSame($sid, $b->service_id);
        $this->assertSame($bid, $b->clinic_branch_id);
        $this->assertSame('Sheet note', $b->admin_notes);
        $this->assertDatabaseHas('website_booking_sheet_changes', ['website_booking_request_id' => $b->id, 'field' => 'patient_name', 'source' => 'import', 'sheet_value' => 'اسم جديد']);
    }

    public function test_disjoint_merge_patches_only_local_changed_cells_preserves_extra_columns_and_immutable_cells(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['admin_notes' => 'Local note'])->saveQuietly();
        $this->gateway->data[0][] = 'Custom';
        $this->gateway->data[1][17] = '=custom formula';
        $this->gateway->data[1][6] = 'user source edit';
        $this->gateway->data[1][7] = 'مؤكد';
        $this->sync->syncNow();
        $this->assertSame('confirmed', $b->fresh()->status);
        $this->assertSame([8 => 'Local note'], end($this->gateway->patches));
        $this->assertSame('=custom formula', $this->gateway->data[1][17]);
        $this->assertSame('user source edit', $this->gateway->data[1][6]);
        $this->assertSame('helal.test', $b->fresh()->source_host);
    }

    public function test_manual_conflict_preserves_baseline_both_values_and_resolves_each_choice(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['admin_notes' => 'Local'])->saveQuietly();
        $this->gateway->data[1][8] = 'Remote';
        $writes = $this->gateway->writes;
        $this->assertSame(1, $this->sync->syncNow()['conflicted']);
        $state = $b->sheetSync()->first();
        $this->assertSame('conflict', $state->status);
        $this->assertSame('', $state->snapshot['admin_notes']);
        $this->assertSame($writes, $this->gateway->writes);
        $this->sync->acknowledgeConflicts($b);
        $this->assertNotEmpty($state->fresh()->conflicts);
        $this->assertTrue($this->sync->resolveConflict($b, 'admin_notes', 'sheet'));
        $this->assertSame('Remote', $b->fresh()->admin_notes);
        $this->assertSame($writes, $this->gateway->writes);
        $b->forceFill(['admin_notes' => 'Local2'])->saveQuietly();
        $this->gateway->data[1][8] = 'Remote2';
        $this->sync->syncNow();
        $this->assertTrue($this->sync->resolveConflict($b, 'admin_notes', 'local'));
        $this->assertSame('Local2', $this->gateway->data[1][8]);
        $this->assertDatabaseHas('website_booking_sheet_changes', ['field' => 'admin_notes', 'source' => 'resolve_sheet', 'selected_value' => 'Remote']);
        $this->assertDatabaseHas('website_booking_sheet_changes', ['field' => 'admin_notes', 'source' => 'resolve_local', 'selected_value' => 'Local2']);
    }

    public function test_resolution_refreshes_conflict_when_sheet_changes_since_review(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['status' => 'contacted'])->saveQuietly();
        $this->gateway->data[1][7] = 'مؤكد';
        $this->sync->syncNow();
        $this->gateway->data[1][7] = 'ملغى';
        $writes = $this->gateway->writes;
        $this->assertFalse($this->sync->resolveConflict($b, 'status', 'local'));
        $this->assertSame($writes, $this->gateway->writes);
        $this->assertSame('cancelled', $b->sheetSync()->first()->conflicts['status']['sheet']);
        $this->assertSame('contacted', $b->fresh()->status);
    }

    public function test_legacy_missing_baseline_and_unknown_labels_create_conflicts_without_overwrite(): void
    {
        foreach (['services', 'clinic_branches'] as $table) {
            if (! Schema::hasTable($table)) {
                Schema::create($table, function ($t) {
                    $t->id();
                    $t->string('name');
                });
            }
        }
        $this->connect();
        $b = $this->booking();
        $b->sheetSync()->update(['snapshot' => ['status' => 'new', 'admin_notes' => '']]);
        $this->gateway->data[1][1] = 'Another name';
        $this->gateway->data[1][4] = 'Unknown service';
        $this->sync->syncNow();
        $conflicts = $b->sheetSync()->first()->conflicts;
        $this->assertSame('missing_baseline', $conflicts['patient_name']['reason']);
        $this->assertSame('invalid_value', $conflicts['service_name']['reason']);
        $this->assertSame($b->patient_name, $b->fresh()->patient_name);
        $this->assertNull($b->fresh()->service_id);
    }

    public function test_pre_patch_reread_detects_new_remote_edit_and_never_overwrites_it(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->forceFill(['admin_notes' => 'Local'])->saveQuietly();
        $next = $this->gateway->rowReads + 2;
        $this->gateway->onRows = function ($n) use ($next) {
            if ($n === $next) {
                $this->gateway->data[1][8] = 'Latest remote';
            }
        };
        $writes = $this->gateway->writes;
        $this->sync->syncNow();
        $this->assertSame($writes, $this->gateway->writes);
        $this->assertSame('Latest remote', $this->gateway->data[1][8]);
        $this->assertSame('Latest remote', $b->sheetSync()->first()->conflicts['admin_notes']['sheet']);
        $this->assertDatabaseHas('website_booking_sheet_changes', ['field' => 'admin_notes', 'sheet_value' => 'Latest remote', 'source' => 'observed']);
    }

    public function test_legacy_conflict_history_is_archived_before_replacement(): void
    {
        $this->connect();
        $b = $this->booking();
        $b->sheetSync()->update(['conflicts' => ['_history' => [['field' => 'status', 'base' => 'new', 'local' => 'contacted', 'sheet' => 'confirmed', 'at' => '2026-01-01', 'reason' => 'old_conflict']]]]);
        $this->sync->syncNow();
        $this->assertDatabaseHas('website_booking_sheet_changes', ['field' => 'status', 'source' => 'legacy_conflict', 'sheet_value' => 'confirmed']);
    }

    public function test_rollbacks_do_not_contact_google(): void
    {
        $this->connect();
        $writes = $this->gateway->writes;
        try {
            DB::transaction(function () {
                $this->booking();
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, WebsiteBookingRequest::count());
        $this->assertSame($writes, $this->gateway->writes);
    }
    public function test_visit_snapshot_columns_upgrade_legacy_header_and_backfill_without_touching_notes(): void
    {
        $b = $this->booking(['booking_time' => '17:13', 'visit_type' => 'follow_up', 'visit_window' => 'من 5 إلى 9', 'visit_window_acknowledged_at' => now(), 'admin_notes' => 'Keep note']);
        $this->sync->connect('https://docs.google.com/spreadsheets/d/abcdefghijklmnopqrstuvw/edit');
        $this->sync->syncNow();
        $this->assertSame('متابعة / استشارة', $this->gateway->data[1][13]);
        $this->assertSame('من 5 إلى 9', $this->gateway->data[1][14]);
        $this->assertNotEmpty($this->gateway->data[1][15]);
        $this->assertSame('17:13', $this->gateway->data[1][16]);
        $this->gateway->data[0] = BookingSheetsSync::LEGACY_HEADERS;
        $this->gateway->data[1] = array_slice($this->gateway->data[1], 0, 13);
        $this->sync->syncNow();
        $this->assertSame(BookingSheetsSync::HEADERS, $this->gateway->data[0]);
        $this->assertSame('متابعة / استشارة', $this->gateway->data[1][13]);
        $this->assertSame('Keep note', $this->gateway->data[1][8]);
        $this->assertSame('17:13', $this->gateway->data[1][16]);
    }

    public function test_english_header_row_upgrades_to_arabic_without_touching_data_rows(): void
    {
        $b = $this->booking(['booking_time' => '17:30', 'visit_type' => 'follow_up', 'visit_window' => 'من 5 إلى 9', 'visit_window_acknowledged_at' => now(), 'admin_notes' => 'Keep note']);
        $this->sync->connect('https://docs.google.com/spreadsheets/d/abcdefghijklmnopqrstuvw/edit');
        $this->sync->syncNow();
        $dataRow = $this->gateway->data[1];
        $this->gateway->data[0] = BookingSheetsSync::ENGLISH_HEADERS;
        $result = $this->sync->syncNow();
        $this->assertSame(0, $result['failed']);
        $this->assertSame(BookingSheetsSync::HEADERS, $this->gateway->data[0]);
        $this->assertSame('متابعة / استشارة', $this->gateway->data[1][13]);
        $this->assertSame('من 5 إلى 9', $this->gateway->data[1][14]);
        $this->assertSame('17:30', $this->gateway->data[1][16]);
        $this->assertSame('Keep note', $this->gateway->data[1][8]);
        $this->assertSame($dataRow, $this->gateway->data[1]);
        // Reconnecting a sheet already carrying Arabic headers keeps them unchanged.
        $this->sync->connect('https://docs.google.com/spreadsheets/d/abcdefghijklmnopqrstuvw/edit');
        $this->assertSame(BookingSheetsSync::HEADERS, $this->gateway->data[0]);
        $this->assertSame($dataRow, $this->gateway->data[1]);
    }

    public function test_legacy_sheet_with_occupied_extension_columns_is_not_overwritten(): void
    {
        $this->booking();
        $this->sync->connect('https://docs.google.com/spreadsheets/d/abcdefghijklmnopqrstuvw/edit');
        $this->gateway->data = [BookingSheetsSync::LEGACY_HEADERS, array_merge(array_fill(0, 13, ''), ['Owner data'])];
        $before = $this->gateway->data;
        try { $this->sync->syncNow(); } catch (\Throwable) {}
        $this->assertSame($before, $this->gateway->data);
    }

}
