<?php

namespace App\Filament\Resources\WebsiteBookingRequestResource\Pages;

use App\Filament\Resources\WebsiteBookingRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteBookingRequest extends EditRecord
{
    protected static string $resource = WebsiteBookingRequestResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return array_intersect_key($data, array_flip(['status', 'admin_notes']));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
