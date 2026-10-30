<?php

namespace App\Services\GoogleSheets;

interface SheetsGateway
{
    public function serviceAccountEmail(): ?string;

    /** Creates only a fresh dedicated tab, or verifies a tab by its persisted ID. */
    public function prepare(string $spreadsheetId, string $title, ?int $sheetId): array;

    public function rows(string $spreadsheetId, string $title): array;

    /** Patch only specified zero-based column indexes on an existing row. */
    public function patch(string $spreadsheetId, string $title, int $row, array $cells): void;

    public function write(string $spreadsheetId, string $title, ?int $row, array $values): void;
}
