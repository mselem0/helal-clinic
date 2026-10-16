<?php

// app/Filament/Resources/ClinicBranchResource/Pages/CreateClinicBranch.php

namespace App\Filament\Resources\ClinicBranchResource\Pages;

use App\Filament\Resources\ClinicBranchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClinicBranch extends CreateRecord
{
    protected static string $resource = ClinicBranchResource::class;
}
