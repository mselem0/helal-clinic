<?php

// app/Filament/Resources/SeoSchemaResource/Pages/EditSeoSchema.php

namespace App\Filament\Resources\SeoSchemaResource\Pages;

use App\Filament\Resources\SeoSchemaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeoSchema extends EditRecord
{
    protected static string $resource = SeoSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
