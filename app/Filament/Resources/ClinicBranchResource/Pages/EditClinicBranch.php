<?php

// app/Filament/Resources/ClinicBranchResource/Pages/EditClinicBranch.php

namespace App\Filament\Resources\ClinicBranchResource\Pages;

use App\Filament\Resources\ClinicBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClinicBranch extends EditRecord
{
    protected static string $resource = ClinicBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
