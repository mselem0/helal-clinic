<?php

namespace App\Support;

use App\Models\ClinicBranch;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class BookingTime
{
    public const STEP_MINUTES = 30;

    public static function enabled(ClinicBranch $branch): bool
    {
        return $branch->booking_schedule_enabled && $branch->booking_time_enabled;
    }

    /** Half-hour choices inside the opening window; the closing boundary is excluded. No capacity limit. */
    public static function slots(ClinicBranch $branch, string $date): array
    {
        $window = BranchSchedule::forDate($branch, $date);
        if (! $window['available'] || ! $window['from'] || ! $window['to']) return [];

        $from = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$window['from'], 'Africa/Cairo');
        $closing = $window['to'] === '00:00'
            ? CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' 00:00', 'Africa/Cairo')->addDay()
            : CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$window['to'], 'Africa/Cairo');

        $offset = ((int) $from->format('i')) % self::STEP_MINUTES;
        if ($offset !== 0) $from = $from->addMinutes(self::STEP_MINUTES - $offset);

        $slots = [];
        for ($slot = $from; $slot->lessThan($closing); $slot = $slot->addMinutes(self::STEP_MINUTES)) {
            $slots[] = $slot->format('H:i');
        }
        return $slots;
    }

    public static function validate(ClinicBranch $branch, string $date, ?string $time): void
    {
        if (! self::enabled($branch)) {
            if ($time !== null && $time !== '') self::fail('اختيار الساعة غير مفعل لهذا الفرع. حدّثي الصفحة.');
            return;
        }
        if (! is_string($time) || ! in_array($time, self::slots($branch, $date), true)) self::fail('اختاري موعدًا من مواعيد الفرع المتاحة كل نصف ساعة.');
        $when = CarbonImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$time, 'Africa/Cairo');
        if ($when->isPast()) self::fail('الموعد المختار مضى. اختاري وقتًا لاحقًا.');
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['booking_time' => $message]);
    }
}
