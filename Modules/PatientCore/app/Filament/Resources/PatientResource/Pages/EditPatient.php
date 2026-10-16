<?php

namespace Modules\PatientCore\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\PatientCore\Filament\Resources\PatientResource;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
