<?php

// app/Filament/Widgets/BookingStats.php

namespace Modules\Booking\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Booking\Models\Booking;

class BookingStats extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Booking::whereDate('booking_date', now())->count();
        $yesterday = Booking::whereDate('booking_date', now()->subDay())->count();

        $diff = $today - $yesterday;
        $trendIcon = $diff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $trendColor = $diff >= 0 ? 'success' : 'danger';
        $trendLabel = abs($diff).' مقارنة بالأمس';

        return [
            Stat::make('حجوزات اليوم', $today)
                ->description($trendLabel)
                ->descriptionIcon($trendIcon)
                ->chart([$yesterday, $today])
                ->color($trendColor),
            Stat::make('معدل التأكيد', function () {
                $total = Booking::where('status', '!=', 'cancelled')->count();
                if ($total == 0) {
                    return '0%';
                }
                $confirmed = Booking::where('status', 'confirmed')->count();

                return round(($confirmed / $total) * 100).'%';
            })
                ->description('نسبة تحويل المرضى')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make('بانتظار المراجعة', Booking::where('status', 'new')->count())
                ->description('تحتاج لإجراء فوري')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),
        ];
    }
}
