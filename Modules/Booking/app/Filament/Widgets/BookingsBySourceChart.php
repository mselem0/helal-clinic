<?php

// app/Filament/Widgets/BookingsBySourceChart.php

namespace Modules\Booking\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Booking\Models\Booking;

class BookingsBySourceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'مصادر الحجوزات (آخر 30 يوم)';

    protected function getData(): array
    {
        $data = Booking::where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('source')
            ->groupBy('source')
            ->selectRaw('source, count(*) as total')
            ->pluck('total', 'source')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'الحجوزات',
                    'data' => array_values($data),
                    'backgroundColor' => ['#002855', '#00B4D8', '#22c55e', '#a855f7', '#f97316'],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
