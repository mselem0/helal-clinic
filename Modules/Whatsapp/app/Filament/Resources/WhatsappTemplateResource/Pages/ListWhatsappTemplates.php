<?php

// app/Filament/Resources/WhatsappTemplateResource/Pages/ListWhatsappTemplates.php

namespace Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource;

class ListWhatsappTemplates extends ListRecords
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
