<?php

namespace Modules\PatientCore\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\PatientCore\Filament\Resources\PatientResource;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
