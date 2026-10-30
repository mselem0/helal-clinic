<?php

namespace App\Support;

use App\Models\ClinicBranch;

final class BookingVisitDetails
{
    public const TYPES = ['first_visit' => 'كشف أول مرة', 'follow_up' => 'متابعة / استشارة'];

    public static function window(ClinicBranch $branch, array $availability): string
    {
        if ($availability['from'] && $availability['to']) {
            $format = static function (string $time): string {
                if ($time === '00:00') return '12 منتصف الليل';
                [$hour, $minute] = array_map('intval', explode(':', $time));
                return ($hour % 12 ?: 12).':'.str_pad((string) $minute, 2, '0', STR_PAD_LEFT).' '.($hour < 12 ? 'صباحًا' : 'مساءً');
            };
            return 'من '.$format($availability['from']).' إلى '.$format($availability['to']);
        }
        return trim((string) $branch->working_hours) ?: 'لم تُضف مواعيد هذا الفرع بعد.';
    }
}
