<?php

// app/Filament/Resources/UserResource/Pages/CreateUser.php

namespace Modules\AccessControl\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AccessControl\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
