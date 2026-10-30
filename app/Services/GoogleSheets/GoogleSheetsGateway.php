<?php

namespace App\Services\GoogleSheets;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\WebsiteBookingRequest;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GoogleSheetsGateway implements SheetsGateway
{
    private ?float $deadline = null;

    public function setDeadline(?float $deadline): void
    {
        $this->deadline = $deadline;
    }

    private function credentials(): array
    {
        $path = config('google_sheets.service_account_path');
        $real = $path ? realpath($path) : false;
        if (! $real || str_starts_with($real, realpath(base_path()).DIRECTORY_SEPARATOR) || ! is_readable($real)) {
            throw new RuntimeException('Service account file must be readable and outside the application directory.');
        }
        $data = json_decode(file_get_contents($real), true);
        if (($data['type'] ?? '') !== 'service_account' || empty($data['client_email']) || empty($data['private_key'])) {
            throw new RuntimeException('Invalid service account configuration.');
        }

        return $data;
    }

    public function serviceAccountEmail(): ?string
    {
        try {
            return $this->credentials()['client_email'];
        } catch (\Throwable) {
            return null;
        }
    }

    protected function createClient(): Client
    {
        $client = new Client;
        $client->setAuthConfig($this->credentials());
        $client->setScopes([Sheets::SPREADSHEETS]);
        $remaining = $this->deadline === null ? 3 : $this->deadline - microtime(true);
        if ($remaining <= 0) {
            throw new RuntimeException('Sync time budget exhausted.');
        }
        $timeout = min((float) config('google_sheets.timeout', 3), $remaining);
        $client->setHttpClient(new HttpClient(['timeout' => $timeout, 'connect_timeout' => min(2, $timeout)]));
        $client->setConfig('retry', ['retries' => 0]);

        return $client;
    }

    private function api(): Sheets
    {
        return new Sheets($this->createClient(), config('google_sheets.api_root'));
    }

    private function range(string $title): string
    {
        return "'".str_replace("'", "''", $title)."'";
    }

    public function prepare(string $spreadsheetId, string $title, ?int $sheetId): array
    {
        $api = $this->api();
        $book = $api->spreadsheets->get($spreadsheetId, ['fields' => 'spreadsheetId,properties.title,sheets.properties']);
        foreach ($book->getSheets() as $sheet) {
            if ($sheetId !== null && $sheet->getProperties()->getSheetId() === $sheetId) {
                $this->ensureBookingControls($api, $spreadsheetId, $sheetId, $sheet->getProperties()->getTitle());

                return ['title' => $book->getProperties()->getTitle(), 'worksheet_title' => $sheet->getProperties()->getTitle(), 'worksheet_id' => $sheetId];
            }
        }
        // Never reuse a matching title: it may contain unrelated user data.
        $freshTitle = mb_substr($title, 0, 65).' '.bin2hex(random_bytes(4));
        $result = $api->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => [['addSheet' => ['properties' => ['title' => $freshTitle]]]]]));
        $id = $result->getReplies()[0]->getAddSheet()->getProperties()->getSheetId();
        $this->ensureBookingControls($api, $spreadsheetId, $id, $freshTitle, true);
        $this->write($spreadsheetId, $freshTitle, 1, BookingSheetsSync::HEADERS);

        return ['title' => $book->getProperties()->getTitle(), 'worksheet_title' => $freshTitle, 'worksheet_id' => $id];
    }

    protected function bookingChoices(): array
    {
        $choices = [7 => array_values(WebsiteBookingRequest::STATUSES)];
        if (Schema::hasTable('services')) {
            $choices[4] = Service::query()->orderBy('name')->pluck('name')->unique()->values()->all();
        }
        if (Schema::hasTable('clinic_branches')) {
            $choices[5] = ClinicBranch::query()->orderBy('name')->pluck('name')->unique()->values()->all();
        }

        return array_filter($choices);
    }

    private function ensureBookingControls(Sheets $api, string $spreadsheetId, int $sheetId, string $title, bool $new = false): void
    {
        $requests = [];
        $cells = [];
        if (! $new) {
            $response = $api->spreadsheets->get($spreadsheetId, [
                'ranges' => [$this->range($title).'!C2:H2'],
                'fields' => 'sheets.data(startColumn,rowData.values(dataValidation,userEnteredFormat.numberFormat))',
            ]);
            $sheet = ($response->getSheets() ?? [])[0] ?? null;
            foreach ($sheet?->getData() ?? [] as $data) {
                foreach ((($data->getRowData() ?? [])[0] ?? null)?->getValues() ?? [] as $offset => $cell) {
                    $cells[($data->getStartColumn() ?? 2) + $offset] = $cell;
                }
            }
        }
        foreach ($this->bookingChoices() as $column => $labels) {
            $rule = ['condition' => ['type' => 'ONE_OF_LIST', 'values' => array_map(fn ($label) => ['userEnteredValue' => $label], $labels)], 'strict' => true, 'showCustomUi' => true];
            $current = ($cells[$column] ?? null)?->getDataValidation();
            if ($current && $current->getStrict() === true && $current->getShowCustomUi() === true && $current->getCondition()?->getType() === 'ONE_OF_LIST' && array_map(fn ($v) => $v->getUserEnteredValue(), $current->getCondition()->getValues() ?? []) === $labels) {
                continue;
            }
            $requests[] = ['setDataValidation' => ['range' => ['sheetId' => $sheetId, 'startRowIndex' => 1, 'startColumnIndex' => $column, 'endColumnIndex' => $column + 1], 'rule' => $rule]];
        }
        $dateRule = ($cells[3] ?? null)?->getDataValidation();
        if (! $dateRule || $dateRule->getStrict() !== true || $dateRule->getCondition()?->getType() !== 'DATE_IS_VALID') {
            $requests[] = ['setDataValidation' => ['range' => ['sheetId' => $sheetId, 'startRowIndex' => 1, 'startColumnIndex' => 3, 'endColumnIndex' => 4], 'rule' => ['condition' => ['type' => 'DATE_IS_VALID'], 'strict' => true]]];
        }
        foreach ([2 => ['type' => 'TEXT'], 3 => ['type' => 'DATE', 'pattern' => 'yyyy-mm-dd']] as $column => $format) {
            $current = ($cells[$column] ?? null)?->getUserEnteredFormat()?->getNumberFormat();
            if ($current?->getType() === $format['type'] && (! isset($format['pattern']) || $current?->getPattern() === $format['pattern'])) {
                continue;
            }
            $requests[] = ['repeatCell' => ['range' => ['sheetId' => $sheetId, 'startRowIndex' => 1, 'startColumnIndex' => $column, 'endColumnIndex' => $column + 1], 'cell' => ['userEnteredFormat' => ['numberFormat' => $format]], 'fields' => 'userEnteredFormat.numberFormat']];
        }
        if ($requests !== []) {
            $api->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $requests]));
        }
    }

    private function exportCell(int $column, mixed $value, bool $header = false): string|int
    {
        $value = (string) ($value ?? '');
        if ($header || $column !== 3 || $value === '') {
            return $value;
        }
        if (! preg_match('/\\A(\\d{4})-(\\d{2})-(\\d{2})\\z/', $value, $parts) || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new \InvalidArgumentException('Booking date must be a valid YYYY-MM-DD date.');
        }
        $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));

        return (int) (new \DateTimeImmutable('1899-12-30', new \DateTimeZone('UTC')))->diff($date)->format('%r%a');
    }

    public function rows(string $spreadsheetId, string $title): array
    {
        return $this->api()->spreadsheets_values->get($spreadsheetId, $this->range($title).'!A:Q', ['valueRenderOption' => 'UNFORMATTED_VALUE'])->getValues() ?? [];
    }

    public function patch(string $spreadsheetId, string $title, int $row, array $cells): void
    {
        if ($row < 2) {
            throw new \InvalidArgumentException('A booking row is required.');
        }
        $ranges = [];
        foreach ($cells as $column => $value) {
            // Internal IDs, source and timestamps must never be patched.
            if (! in_array($column, [1, 2, 3, 4, 5, 7, 8, 13, 14, 15, 16], true)) {
                throw new \InvalidArgumentException('This column is not editable.');
            }
            $ranges[] = ['range' => $this->range($title).'!'.chr(65 + $column).$row, 'values' => [[$this->exportCell($column, $value)]]];
        }
        if ($ranges === []) {
            return;
        }
        $this->api()->spreadsheets_values->batchUpdate($spreadsheetId, new BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW', 'data' => $ranges,
        ]));
    }

    public function write(string $spreadsheetId, string $title, ?int $row, array $values): void
    {
        $api = $this->api();
        $body = new ValueRange(['values' => [array_map(fn ($v, $column) => $this->exportCell($column, $v, $row === 1), $values, array_keys($values))]]);
        if ($row !== null) {
            $api->spreadsheets_values->update($spreadsheetId, $this->range($title).'!A'.$row.':Q'.$row, $body, ['valueInputOption' => 'RAW']);
        } else {
            $api->spreadsheets_values->append($spreadsheetId, $this->range($title).'!A:Q', $body, ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']);
        }
    }
}
