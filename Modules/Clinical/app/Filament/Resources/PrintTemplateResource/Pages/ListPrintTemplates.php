<?php

namespace Modules\Clinical\Filament\Resources\PrintTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Clinical\Filament\Resources\PrintTemplateResource;

class ListPrintTemplates extends ListRecords
{
    protected static string $resource = PrintTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
