<?php

namespace App\Support;

use App\Models\ClinicBranch;
use DateTimeImmutable;

class BranchSchedule
{
    /**
     * Resolve the booking window for a branch on a YYYY-MM-DD date.
     * A disabled schedule preserves the historical unrestricted booking behavior.
     *
     * @return array{available: bool, from: ?string, to: ?string}
     */
    public static function forDate(ClinicBranch $branch, string $date): array
    {
        if (! $branch->booking_schedule_enabled) {
            return ['available' => true, 'from' => null, 'to' => null];
        }

        if (! self::validDate($date)) {
            return self::unavailable();
        }

        foreach (is_array($branch->schedule_exceptions) ? $branch->schedule_exceptions : [] as $exception) {
            if (! is_array($exception) || ($exception['date'] ?? null) !== $date) {
                continue;
            }

            if (($exception['closed'] ?? null) === true) {
                return self::unavailable();
            }

            if (($exception['closed'] ?? null) !== false) {
                return self::unavailable();
            }

            return self::window($exception['from'] ?? null, $exception['to'] ?? null);
        }

        $weekday = (new DateTimeImmutable($date))->format('w');
        $day = is_array($branch->weekly_schedule) ? ($branch->weekly_schedule[$weekday] ?? null) : null;

        if (! is_array($day) || ($day['open'] ?? null) !== true) {
            return self::unavailable();
        }

        return self::window($day['from'] ?? null, $day['to'] ?? null);
    }

    /** @return array{available: bool, from: ?string, to: ?string} */
    private static function window(mixed $from, mixed $to): array
    {
        if (! self::validTime($from) || ! self::validTime($to) || $from >= $to) {
            return self::unavailable();
        }

        return ['available' => true, 'from' => $from, 'to' => $to];
    }

    private static function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private static function validTime(mixed $time): bool
    {
        return is_string($time) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $time) === 1;
    }

    /** @return array{available: false, from: null, to: null} */
    private static function unavailable(): array
    {
        return ['available' => false, 'from' => null, 'to' => null];
    }
}
