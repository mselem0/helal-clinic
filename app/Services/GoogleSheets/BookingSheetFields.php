<?php

namespace App\Services\GoogleSheets;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\WebsiteBookingRequest;
use DateTimeImmutable;

class BookingSheetFields
{
    public const COLUMNS = ['patient_name' => 1, 'patient_phone' => 2, 'booking_date' => 3, 'service_name' => 4, 'branch_name' => 5, 'status' => 7, 'admin_notes' => 8];

    public function local(WebsiteBookingRequest $b): array
    {
        $out = [];
        foreach (self::COLUMNS as $field => $column) {
            $out[$field] = $field === 'booking_date' ? $b->booking_date?->format('Y-m-d') : (string) ($b->$field ?? '');
        }

        return $out;
    }

    public function canonical(string $field, mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($field === 'status') {
            return array_search($value, WebsiteBookingRequest::STATUSES, true) ?: $value;
        }
        if ($field === 'patient_phone') {
            $value = strtr($value, array_combine(preg_split('//u', '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹', -1, PREG_SPLIT_NO_EMPTY), str_split('01234567890123456789')));
            $value = preg_replace('/[\s()\-]/u', '', $value);
            if (str_starts_with($value, '00')) {
                $value = substr($value, 2);
            } if (str_starts_with($value, '+')) {
                $value = substr($value, 1);
            }
        }
        if ($field === 'booking_date' && is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
            return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) $value.' days')->format('Y-m-d');
        }

        return $field === 'admin_notes' ? $value : trim($value);
    }

    public function remote(array $row): array
    {
        $out = [];
        foreach (self::COLUMNS as $field => $column) {
            $out[$field] = $this->canonical($field, $row[$column] ?? '');
        }

        return $out;
    }

    public function exported(string $field, string $value): string
    {
        return $field === 'status' ? (WebsiteBookingRequest::STATUSES[$value] ?? $value) : $value;
    }

    /** Validate and resolve only trusted labels; never accept IDs from the sheet. */
    public function updates(string $field, string $value): ?array
    {
        if ($field === 'patient_name' && ($value === '' || mb_strlen($value) > 70)) {
            return null;
        }
        if ($field === 'patient_phone' && ! preg_match('/^[0-9]{7,15}$/', $value)) {
            return null;
        }
        if ($field === 'booking_date') {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (! $d || $d->format('Y-m-d') !== $value) {
                return null;
            }
        }
        if ($field === 'status' && ! array_key_exists($value, WebsiteBookingRequest::STATUSES)) {
            return null;
        }
        if ($field === 'admin_notes' && mb_strlen($value) > 10000) {
            return null;
        }
        if (in_array($field, ['service_name', 'branch_name'], true)) {
            $class = $field === 'service_name' ? Service::class : ClinicBranch::class;
            $matches = $class::query()->where('name', $value)->get()->filter(fn ($m) => trim($m->name) === $value);
            if ($matches->count() !== 1) {
                return null;
            }

            return [$field => $value, $field === 'service_name' ? 'service_id' : 'clinic_branch_id' => $matches->first()->id];
        }

        return [$field => $value === '' && $field === 'admin_notes' ? null : $value];
    }
}
