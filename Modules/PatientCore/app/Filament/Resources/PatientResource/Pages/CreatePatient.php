<?php

namespace Modules\PatientCore\Filament\Resources\PatientResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\PatientCore\Filament\Resources\PatientResource;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
}
