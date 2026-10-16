<?php

// app/Filament/Resources/WhatsappTemplateResource/Pages/EditWhatsappTemplate.php

namespace Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource;

class EditWhatsappTemplate extends EditRecord
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
