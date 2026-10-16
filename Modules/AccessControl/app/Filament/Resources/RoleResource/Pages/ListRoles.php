<?php

namespace Modules\AccessControl\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AccessControl\Filament\Resources\RoleResource;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
