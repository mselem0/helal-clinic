<?php

namespace App\Filament\Resources\WebsiteBookingRequestResource\Pages;

use App\Filament\Resources\WebsiteBookingRequestResource;
use App\Services\WebsiteBookingRequestExport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteBookingRequests extends ListRecords
{
    protected static string $resource = WebsiteBookingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('export')->label('تصدير Excel')->icon('heroicon-o-arrow-down-tray')
            ->visible(fn () => WebsiteBookingRequestResource::canViewAny())
            ->action(function () {
                abort_unless(WebsiteBookingRequestResource::canViewAny(), 403);

                return app(WebsiteBookingRequestExport::class)->download($this->getFilteredTableQuery());
            })];
    }
}
