<?php

// app/Filament/Widgets/ContentStatusWidget.php

namespace App\Filament\Widgets;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\Video;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStatusWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('الخدمات', Service::where('is_active', true)->count().' نشطة')
                ->description('من إجمالي '.Service::count()),
            Stat::make('الفيديوهات', Video::where('is_active', true)->count().' نشط'),
            Stat::make('آراء المرضى', Testimonial::where('is_active', true)->count().' نشط'),
            Stat::make('الفروع', ClinicBranch::where('is_active', true)->count().' نشط'),
        ];
    }
}
