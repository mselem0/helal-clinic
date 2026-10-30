<?php

namespace App\Services\GoogleSheets;

use App\Models\GoogleSheetsConnection;
use App\Models\WebsiteBookingRequest;
use App\Models\WebsiteBookingSheetChange;
use App\Models\WebsiteBookingSheetSync;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingSheetsSync
{
    public const LEGACY_HEADERS = ['request_key', 'patient_name', 'patient_phone', 'booking_date', 'service_name', 'branch_name', 'source_host', 'status', 'admin_notes', 'created_at', 'updated_at', 'local_id', 'sync_version'];

    public const ENGLISH_HEADERS = [...self::LEGACY_HEADERS, 'visit_type', 'visit_window', 'visit_window_acknowledged_at', 'booking_time'];

    public const ARABIC_ACK_HEADERS = ['مفتاح الطلب', 'الاسم', 'الهاتف', 'تاريخ الزيارة', 'الخدمة', 'الفرع', 'المصدر', 'الحالة', 'ملاحظات الإدارة', 'وقت الطلب', 'آخر تحديث', 'المعرف المحلي', 'إصدار المزامنة', 'نوع الزيارة', 'فترة الزيارة المعروضة', 'وقت تأكيد قراءة الموعد', 'الساعة المختارة (القاهرة)'];

    /** Arabic column titles for clinic staff; layout fingerprints above keep the upgrade chain verifiable. */
    public const HEADERS = ['مفتاح الطلب', 'الاسم', 'الهاتف', 'تاريخ الزيارة', 'الخدمة', 'الفرع', 'المصدر', 'الحالة', 'ملاحظات الإدارة', 'وقت الطلب', 'آخر تحديث', 'المعرف المحلي', 'إصدار المزامنة', 'نوع الزيارة', 'فترة الزيارة المعروضة', 'الساعة المختارة (القاهرة)'];

    public function __construct(private SheetsGateway $gateway) {}

    public function connection(): GoogleSheetsConnection
    {
        return GoogleSheetsConnection::firstOrCreate(['id' => 1], ['installation_key' => (string) Str::uuid()]);
    }

    public function disconnect(): void
    {
        Cache::lock('website-booking-sheets-sync', 120)->block(1, function () {
            DB::transaction(function () {
                $connection = $this->connection();
                $connection->update(['spreadsheet_id' => null, 'spreadsheet_title' => null, 'worksheet_title' => null, 'worksheet_id' => null, 'connected_at' => null, 'last_synced_at' => null, 'last_error' => null, 'retry_after' => null]);
                WebsiteBookingSheetSync::query()->update(['snapshot' => null, 'status' => 'pending', 'retry_after' => null, 'last_error' => null, 'last_synced_at' => null]);
            });
        });
    }

    public function acknowledgeConflicts(WebsiteBookingRequest $booking): void
    {
        // Unresolved conflicts require an explicit local/sheet choice and cannot be discarded.
    }

    public function serviceAccountEmail(): ?string
    {
        return $this->gateway->serviceAccountEmail();
    }

    public function connect(string $link): GoogleSheetsConnection
    {
        $id = trim($link);
        if (preg_match('~^https://docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)(?:/|$|\?)~', $id, $m)) {
            $id = $m[1];
        }
        if (! preg_match('/^[a-zA-Z0-9_-]{20,}$/', $id)) {
            throw ValidationException::withMessages(['spreadsheet_url' => 'أدخل رابط Google Sheets صالحًا.']);
        }

        return Cache::lock('website-booking-sheets-sync', 120)->block(1, function () use ($id) {
            $c = $this->connection();
            if (method_exists($this->gateway, 'setDeadline')) {
                $this->gateway->setDeadline(microtime(true) + 12);
            }
            try {
                $oldId = $c->spreadsheet_id;
                $tab = $this->gateway->prepare($id, config('google_sheets.worksheet_prefix').' '.substr($c->installation_key, 0, 8), $c->spreadsheet_id === $id ? $c->worksheet_id : null);
                // Reconnecting must not claim owner-created extension columns.
                if ($oldId === $id && $c->worksheet_id !== null) {
                    $existingRows = $this->gateway->rows($id, $tab['worksheet_title']);
                    $header = array_slice($existingRows[0] ?? [], 0, count(self::HEADERS));
                    if ($header !== self::HEADERS) {
                        if ($header === array_slice(self::ARABIC_ACK_HEADERS, 0, count(self::HEADERS)) || $header === array_slice(self::ENGLISH_HEADERS, 0, count(self::HEADERS))) {
                            $existingRows = $this->migrateAckColumn($id, $tab['worksheet_title'], $existingRows);
                        } elseif (array_slice($existingRows[0] ?? [], 0, 13) !== self::LEGACY_HEADERS) {
                            throw new \RuntimeException('Sheet headers changed.');
                        } else {
                            foreach ($existingRows as $row) {
                                if (count(array_filter(array_slice($row, 13), fn ($v) => (string) $v !== '')) > 0) throw new \RuntimeException('Sheet extension columns are occupied.');
                            }
                        }
                    }
                }
                // A harmless RAW write verifies edit permission even when reconnecting.
                $this->gateway->write($id, $tab['worksheet_title'], 1, self::HEADERS);
                $c->fill(['spreadsheet_id' => $id, 'spreadsheet_title' => $tab['title'], 'worksheet_title' => $tab['worksheet_title'], 'worksheet_id' => $tab['worksheet_id'], 'connected_at' => now(), 'last_error' => null, 'retry_after' => null])->save();
                if ($oldId !== $id) {
                    WebsiteBookingSheetSync::query()->update(['status' => 'pending', 'snapshot' => null, 'retry_after' => null]);
                }

                return $c;
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['spreadsheet_url' => 'تعذر الاتصال. تحقق من ملف حساب الخدمة ومشاركة الجدول معه بصلاحية محرر.']);
            }
        });
    }

    public function syncRequest(WebsiteBookingRequest $booking): bool
    {
        $state = WebsiteBookingSheetSync::firstOrCreate(['website_booking_request_id' => $booking->id]);
        $state->update(['status' => 'pending']);
        try {
            return (bool) Cache::lock('website-booking-sheets-sync', 120)->get(fn () => $this->run($booking->id)['synced'] > 0);
        } catch (\Throwable $e) {
            $this->fail($state, $e);
            if ((int) $e->getCode() === 429) {
                $this->connection()->update(['retry_after' => now()->addMinute(), 'last_error' => $this->error($e)]);
            }

            return false;
        }
    }

    public function syncNow(): array
    {
        try {
            return Cache::lock('website-booking-sheets-sync', 120)->get(fn () => $this->run()) ?: ['synced' => 0, 'failed' => 0, 'conflicted' => 0, 'busy' => true];
        } catch (\Throwable $e) {
            $this->connection()->update(['last_error' => $this->error($e), 'retry_after' => (int) $e->getCode() === 429 ? now()->addMinute() : null]);

            return ['synced' => 0, 'failed' => 1, 'conflicted' => 0, 'busy' => false];
        }
    }

    private function run(?int $only = null): array
    {
        $started = microtime(true);
        if (method_exists($this->gateway, 'setDeadline')) {
            $this->gateway->setDeadline($started + ($only === null ? 40 : 3));
        }
        $c = $this->connection();
        $result = ['synced' => 0, 'failed' => 0, 'conflicted' => 0, 'busy' => false];
        if ($c->retry_after?->isFuture()) {
            return $result + ['deferred' => true];
        }
        if (! $c->spreadsheet_id) {
            return $result;
        }
        if ($only === null || ! $c->worksheet_id) {
            $tab = $this->gateway->prepare($c->spreadsheet_id, $c->worksheet_title, $c->worksheet_id);
            $c->update(['worksheet_title' => $tab['worksheet_title'], 'worksheet_id' => $tab['worksheet_id'], 'spreadsheet_title' => $tab['title']]);
        }
        $rows = $this->gateway->rows($c->spreadsheet_id, $c->worksheet_title);
        $header = array_slice($rows[0] ?? [], 0, count(self::HEADERS));
        if ($header !== self::HEADERS) {
            if (array_slice($rows[0] ?? [], 0, 17) === self::ARABIC_ACK_HEADERS || array_slice($rows[0] ?? [], 0, 17) === self::ENGLISH_HEADERS) {
                $rows = $this->migrateAckColumn($c->spreadsheet_id, $c->worksheet_title, $rows);
            } elseif (array_slice($rows[0] ?? [], 0, 13) === self::LEGACY_HEADERS && count(array_filter(array_slice($rows[0], 13), fn ($v) => (string) $v !== '')) === 0) {
                foreach (array_slice($rows, 1) as $row) {
                    if (count(array_filter(array_slice($row, 13), fn ($v) => (string) $v !== '')) > 0) {
                        throw new \RuntimeException('Sheet extension columns are occupied.');
                    }
                }
                $this->gateway->write($c->spreadsheet_id, $c->worksheet_title, 1, self::HEADERS);
                $rows = $this->gateway->rows($c->spreadsheet_id, $c->worksheet_title);
            }
        }
        $index = $this->index($rows);
        $query = WebsiteBookingRequest::query()->when($only, fn ($q) => $q->whereKey($only))->orderByRaw('(SELECT last_synced_at FROM website_booking_sheet_syncs WHERE website_booking_request_id = website_booking_requests.id) ASC')->orderBy('id');
        $writes = 0;
        foreach ($query->cursor() as $booking) {
            if (microtime(true) - $started > ($only === null ? 40 : 3) || $writes >= 45) {
                break;
            }
            $state = WebsiteBookingSheetSync::firstOrCreate(['website_booking_request_id' => $booking->id]);
            if ($state->retry_after?->isFuture()) {
                continue;
            }
            if (! $booking->request_key) {
                $booking->forceFill(['request_key' => (string) Str::uuid()])->saveQuietly();
            }
            try {
                $match = $this->match($index, $booking->request_key);
                $remote = $match[1] ?? null;
                if ($remote === null) {
                    $booking->refresh();
                    $snapshot = (new BookingSheetFields)->local($booking);
                    $this->audit($booking, '*', null, json_encode($snapshot), null, json_encode($snapshot), 'append');
                    $this->gateway->write($c->spreadsheet_id, $c->worksheet_title, null, $this->values($booking));
                    $writes++;
                    $this->complete($booking, $state, $snapshot, []);
                } else {
                    [$booking,$snapshot,$conflicts,$patch] = DB::transaction(fn () => $this->merge($booking, $state, $remote));
                    // These three columns are a read-only snapshot of the original public booking.
                    $details = $this->values($booking);
                    foreach ([13, 14, 15] as $column) {
                        if ((string) ($remote[$column] ?? '') !== $details[$column]) $patch[$column] = $details[$column];
                    }
                    if ($patch) {
                        // Remap immediately before patching; retain any newly observed editable revision.
                        $latest = $this->match($this->index($this->gateway->rows($c->spreadsheet_id, $c->worksheet_title)), $booking->request_key);
                        $current = $booking->fresh();
                        $fields = new BookingSheetFields;
                        $stale = $latest === null;
                        foreach (BookingSheetFields::COLUMNS as $field => $column) {
                            if (($latest[1][$column] ?? '') !== ($remote[$column] ?? '') || $fields->local($current)[$field] !== $fields->local($booking)[$field]) {
                                $stale = true;
                            }
                        }
                        if ($stale) {
                            if ($latest !== null) {
                                DB::transaction(fn () => $this->merge($current, $state, $latest[1]));
                            }
                            $state->refresh();
                            $state->update(['status' => $state->conflicts ? 'conflict' : 'pending']);
                            if ($state->conflicts) {
                                $result['conflicted']++;
                            }

                            continue;
                        }
                        $this->gateway->patch($c->spreadsheet_id, $c->worksheet_title, $latest[0], $patch);
                        $writes++;
                    }
                    $this->complete($booking, $state, $snapshot, $conflicts);
                    if ($conflicts) {
                        $result['conflicted']++;
                    }
                }
                $result['synced']++;
            } catch (\Throwable $e) {
                $this->fail($state, $e);
                $result['failed']++;
                if ((int) $e->getCode() === 429) {
                    $c->update(['retry_after' => now()->addMinute()]);
                    break;
                }
            }
        }
        $c->update(['last_synced_at' => now(), 'last_error' => $result['failed'] ? 'تعذرت مزامنة بعض الطلبات. راجع حالة كل طلب.' : null]);

        return $result;
    }

    /** Retire the acknowledgement column in place: shift P:Q data left and clear the vacated Q cell. */
    private function migrateAckColumn(string $spreadsheetId, string $title, array $rows): array
    {
        if (count($rows) > 401) throw new \RuntimeException('Sheet has too many rows for automatic column migration.');
        foreach ($rows as $row) {
            if (count(array_filter(array_slice($row, 17), fn ($v) => (string) $v !== '')) > 0) throw new \RuntimeException('Owner columns beyond Q block the column migration.');
        }
        foreach (array_slice($rows, 1, null, true) as $i => $row) {
            // Sheets returns stored dates as serial numbers; restore the textual form the writer validates.
            foreach ($row as $column => $value) {
                if ($column === 3 && is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
                    $row[$column] = (new \DateTimeImmutable('1899-12-30'))->modify('+'.(int) $value.' days')->format('Y-m-d');
                } else {
                    $row[$column] = (string) ($value ?? '');
                }
            }
            array_splice($row, 15, 1);
            $this->gateway->write($spreadsheetId, $title, $i + 1, array_pad($row, 17, ''));
        }
        $this->gateway->write($spreadsheetId, $title, 1, array_pad(self::HEADERS, 17, ''));
        return $this->gateway->rows($spreadsheetId, $title);
    }

    private function index(array $rows): array
    {
        if (array_slice($rows[0] ?? [], 0, count(self::HEADERS)) !== self::HEADERS) {
            throw new \RuntimeException('Sheet headers changed.');
        }
        $index = [];
        foreach (array_slice($rows, 1, null, true) as $i => $row) {
            if ((string) ($row[0] ?? '') !== '') {
                $index[(string) $row[0]][] = [$i + 1, $row];
            }
        }

        return $index;
    }

    private function match(array $index, string $key): ?array
    {
        $matches = $index[$key] ?? [];
        if (count($matches) > 1) {
            throw new \RuntimeException('Duplicate request keys.');
        }

        return $matches[0] ?? null;
    }

    private function merge(WebsiteBookingRequest $booking, WebsiteBookingSheetSync $state, array $remote): array
    {
        $booking = WebsiteBookingRequest::whereKey($booking->id)->lockForUpdate()->firstOrFail();
        $fields = new BookingSheetFields;
        $local = $fields->local($booking);
        $sheet = $fields->remote($remote);
        $snapshot = $state->fresh()->snapshot ?? [];
        $conflicts = $state->fresh()->conflicts ?? [];
        foreach ($conflicts as $oldField => $oldEntry) {
            if ($oldField === '_history') {
                foreach ($oldEntry as $historic) {
                    $this->audit($booking, $historic['field'] ?? '*', $historic['base'] ?? null, $historic['local'] ?? null, $historic['sheet'] ?? null, null, 'legacy_conflict', ['at' => $historic['at'] ?? null, 'reason' => $historic['reason'] ?? null]);
                }
            } elseif (is_array($oldEntry)) {
                $this->audit($booking, $oldField, $oldEntry['base'] ?? null, $oldEntry['local'] ?? null, $oldEntry['sheet'] ?? null, null, 'prior_conflict', ['at' => $oldEntry['at'] ?? null, 'reason' => $oldEntry['reason'] ?? null]);
            }
        }
        unset($conflicts['_history']);
        $patch = [];
        $updates = [];
        foreach (BookingSheetFields::COLUMNS as $field => $column) {
            $l = $local[$field];
            $r = $sheet[$field];
            $has = array_key_exists($field, $snapshot);
            $base = $snapshot[$field] ?? null;
            if (! $has || $l !== $base || $r !== $base) {
                $this->audit($booking, $field, $base, $l, $r, null, 'observed', ['raw_sheet' => $remote[$column] ?? '']);
            }
            if ($l === $r) {
                $snapshot[$field] = $l;
                unset($conflicts[$field]);

                continue;
            }
            $valid = $fields->updates($field, $r);
            $reason = $valid === null ? 'invalid_value' : (! $has ? 'missing_baseline' : ((isset($conflicts[$field]) || ($l !== $base && $r !== $base)) ? 'concurrent_edit' : null));
            if ($reason) {
                $entry = $this->conflict($base, $l, $r, $reason, $remote[$column] ?? '');
                $conflicts[$field] = ($conflicts[$field]['version'] ?? null) === $entry['version'] ? $conflicts[$field] : $entry;
                $this->audit($booking, $field, $base, $l, $r, null, 'conflict', ['reason' => $reason]);

                continue;
            }
            if ($r !== $base) {
                $this->audit($booking, $field, $base, $l, $r, $r, 'import');
                $updates = array_merge($updates, $valid);
                $snapshot[$field] = $r;
            } elseif ($l !== $base) {
                $this->audit($booking, $field, $base, $l, $r, $l, 'push');
                $patch[$column] = $fields->exported($field, $l);
                $snapshot[$field] = $l;
            }
        }
        if ($updates) {
            $booking->forceFill($updates)->saveQuietly();
        }
        // Persist conflicts before attempting any external write; failed calls cannot erase evidence.
        $state->update(['conflicts' => $conflicts ?: null]);

        return [$booking, $snapshot, $conflicts, $patch];
    }

    private function complete(WebsiteBookingRequest $booking, WebsiteBookingSheetSync $state, array $snapshot, array $conflicts): void
    {
        DB::transaction(function () use ($booking, $state, $snapshot, $conflicts) {
            $fresh = WebsiteBookingRequest::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $changed = $fresh->getAttributes() !== $booking->getAttributes();
            $state->update(['status' => $conflicts ? 'conflict' : ($changed ? 'pending' : 'synced'), 'snapshot' => $snapshot, 'conflicts' => $conflicts ?: null, 'last_error' => null, 'retry_after' => null, 'last_synced_at' => now()]);
        });
    }

    private function conflict(?string $base, string $local, string $sheet, string $reason, mixed $raw): array
    {
        return ['base' => $base, 'local' => $local, 'sheet' => $sheet, 'sheet_raw' => $raw, 'reason' => $reason, 'at' => now()->toIso8601String(), 'version' => hash('sha256', json_encode([$base, $local, $sheet, $raw]))];
    }

    private function audit(WebsiteBookingRequest $booking, string $field, ?string $base, ?string $local, ?string $sheet, ?string $selected, string $source, array $metadata = []): void
    {
        $data = ['website_booking_request_id' => $booking->id, 'field' => $field, 'base_value' => $base, 'local_value' => $local, 'sheet_value' => $sheet, 'selected_value' => $selected, 'source' => $source, 'metadata' => $metadata];
        $last = WebsiteBookingSheetChange::where('website_booking_request_id', $booking->id)->where('field', $field)->where('source', $source)->latest('id')->first();
        if ($last && $last->base_value === $base && $last->local_value === $local && $last->sheet_value === $sheet && $last->selected_value === $selected && ($last->metadata ?? []) === $metadata) {
            return;
        }
        WebsiteBookingSheetChange::create($data + ['created_at' => now()]);
    }

    public function resolveConflict(WebsiteBookingRequest $booking, string $field, string $choice): bool
    {
        if (! array_key_exists($field, BookingSheetFields::COLUMNS) || ! in_array($choice, ['local', 'sheet'], true)) {
            throw new \InvalidArgumentException('Invalid conflict choice.');
        }

        try {
            return (bool) Cache::lock('website-booking-sheets-sync', 120)->block(1, function () use ($booking, $field, $choice) {
                $c = $this->connection();
                if (! $c->spreadsheet_id || $c->retry_after?->isFuture()) {
                    return false;
                }
                if (method_exists($this->gateway, 'setDeadline')) {
                    $this->gateway->setDeadline(microtime(true) + 12);
                }
                $state = $booking->sheetSync()->first();
                $entry = $state?->conflicts[$field] ?? null;
                if (! $entry) {
                    return false;
                }
                $latest = $this->match($this->index($this->gateway->rows($c->spreadsheet_id, $c->worksheet_title)), $booking->request_key);
                if (! $latest) {
                    $state->update(['status' => 'pending']);

                    return false;
                }
                $fields = new BookingSheetFields;
                $booking->refresh();
                $local = $fields->local($booking)[$field];
                $sheet = $fields->remote($latest[1])[$field];
                $raw = $latest[1][BookingSheetFields::COLUMNS[$field]] ?? '';
                $version = $this->conflict($entry['base'] ?? null, $local, $sheet, $entry['reason'], $raw)['version'];
                if (($entry['version'] ?? null) !== $version) {
                    $conflicts = $state->conflicts;
                    $conflicts[$field] = $this->conflict($entry['base'] ?? null, $local, $sheet, 'changed_since_review', $raw);
                    $this->audit($booking, $field, $entry['base'] ?? null, $local, $sheet, null, 'resolution_stale');
                    $state->update(['conflicts' => $conflicts, 'status' => 'conflict']);

                    return false;
                }
                $selected = $choice === 'local' ? $local : $sheet;
                $updates = $choice === 'local' && in_array($field, ['service_name', 'branch_name'], true) ? [$field => $selected] : $fields->updates($field, $selected);
                if ($updates === null) {
                    return false;
                }
                $this->audit($booking, $field, $entry['base'] ?? null, $local, $sheet, $selected, 'resolve_'.$choice);
                if ($choice === 'local') {
                    $this->gateway->patch($c->spreadsheet_id, $c->worksheet_title, $latest[0], [BookingSheetFields::COLUMNS[$field] => $fields->exported($field, $selected)]);
                }

                return DB::transaction(function () use ($booking, $state, $field, $choice, $updates, $selected, $fields, $local) {
                    $fresh = WebsiteBookingRequest::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                    if ($fields->local($fresh)[$field] !== $local) {
                        $state->update(['status' => 'pending']);

                        return false;
                    }
                    if ($choice === 'sheet') {
                        $fresh->forceFill($updates)->saveQuietly();
                    }
                    $snapshot = $state->snapshot ?? [];
                    $snapshot[$field] = $selected;
                    $conflicts = $state->conflicts ?? [];
                    unset($conflicts[$field]);
                    $state->update(['snapshot' => $snapshot, 'conflicts' => $conflicts ?: null, 'status' => $conflicts ? 'conflict' : 'pending', 'retry_after' => null]);

                    return true;
                });
            });
        } catch (\Throwable $e) {
            if ($state = $booking->sheetSync()->first()) {
                $this->fail($state, $e);
            }
            $this->connection()->update(['last_error' => $this->error($e), 'retry_after' => (int) $e->getCode() === 429 ? now()->addMinute() : null]);

            return false;
        }
    }

    private function values(WebsiteBookingRequest $b): array
    {
        return array_map(fn ($v) => (string) ($v ?? ''), [$b->request_key, $b->patient_name, $b->patient_phone, $b->booking_date?->format('Y-m-d'), $b->service_name, $b->branch_name, $b->source_host, WebsiteBookingRequest::STATUSES[$b->status] ?? $b->status, $b->admin_notes, $b->created_at?->toIso8601String(), $b->updated_at?->toIso8601String(), $b->id, '1', WebsiteBookingRequest::VISIT_TYPES[$b->visit_type] ?? '', $b->visit_window, $b->booking_time]);
    }

    private function error(\Throwable $e): string
    {
        // Provider messages can contain request data and credentials; never persist them.
        return match ((int) $e->getCode()) {
            429 => 'تم بلوغ حد Google. ستعاد المحاولة تلقائيًا.',401,403 => 'رُفض الوصول إلى Google. تحقق من بيانات الحساب وصلاحية مشاركة الجدول.',404 => 'الجدول غير متاح. تحقق من رابط الاتصال.',default => 'تعذرت المزامنة. ستعاد المحاولة تلقائيًا؛ تحقق من الإعدادات وعناوين الأعمدة وعدم تكرار معرّفات الطلبات.'
        };
    }

    private function fail(WebsiteBookingSheetSync $state, \Throwable $e): void
    {
        $state->update(['status' => 'failed', 'last_error' => $this->error($e), 'retry_after' => now()->addMinute()]);
    }
}
