<?php

// app/Filament/Resources/BookingResource/Pages/CreateBooking.php

namespace Modules\Booking\Filament\Resources\BookingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Booking\Filament\Resources\BookingResource;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
