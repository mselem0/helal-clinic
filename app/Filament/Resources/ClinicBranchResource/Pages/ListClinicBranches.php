<?php

// app/Filament/Resources/ClinicBranchResource/Pages/ListClinicBranches.php

namespace App\Filament\Resources\ClinicBranchResource\Pages;

use App\Filament\Resources\ClinicBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClinicBranches extends ListRecords
{
    protected static string $resource = ClinicBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
