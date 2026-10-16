<?php

// app/Filament/Resources/UserResource/Pages/EditUser.php

namespace Modules\AccessControl\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AccessControl\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
