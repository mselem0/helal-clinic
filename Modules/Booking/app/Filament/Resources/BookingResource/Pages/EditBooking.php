<?php

// app/Filament/Resources/BookingResource/Pages/EditBooking.php

namespace Modules\Booking\Filament\Resources\BookingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Booking\Filament\Resources\BookingResource;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
