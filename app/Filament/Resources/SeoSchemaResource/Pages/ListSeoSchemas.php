<?php

// app/Filament/Resources/SeoSchemaResource/Pages/ListSeoSchemas.php

namespace App\Filament\Resources\SeoSchemaResource\Pages;

use App\Filament\Resources\SeoSchemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeoSchemas extends ListRecords
{
    protected static string $resource = SeoSchemaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
