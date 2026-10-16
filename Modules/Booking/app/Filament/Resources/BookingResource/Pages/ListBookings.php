<?php

// app/Filament/Resources/BookingResource/Pages/ListBookings.php

namespace Modules\Booking\Filament\Resources\BookingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Booking\Filament\Resources\BookingResource;
use Modules\Booking\Filament\Widgets\BookingStats;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('calendar')
                ->label('عرض التقويم')
                ->url(static::getResource()::getUrl('calendar'))
                ->color('info')
                ->icon('heroicon-o-calendar'),
            Actions\Action::make('kanban')
                ->label('لوحة كانبان')
                ->url(static::getResource()::getUrl('kanban'))
                ->color('gray')
                ->icon('heroicon-o-view-columns'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingStats::class,
        ];
    }
}
