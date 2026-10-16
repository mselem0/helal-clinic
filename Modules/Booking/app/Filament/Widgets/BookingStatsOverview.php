<?php

// app/Filament/Widgets/BookingStatsOverview.php

namespace Modules\Booking\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Booking\Models\Booking;

class BookingStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('حجوزات اليوم', Booking::whereDate('created_at', now())->count())
                ->icon('heroicon-o-calendar'),
            Stat::make('حجوزات هذا الشهر', Booking::whereMonth('created_at', now()->month)->count())
                ->description('إجمالي حجوزات الشهر الحالي')
                ->color('success'),
            Stat::make('حجوزات جديدة', Booking::where('status', 'new')->count())
                ->description('بانتظار التأكيد')
                ->color('warning'),
        ];
    }
}
