<?php

// app/Filament/Resources/WhatsappTemplateResource/Pages/CreateWhatsappTemplate.php

namespace Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource;

class CreateWhatsappTemplate extends CreateRecord
{
    protected static string $resource = WhatsappTemplateResource::class;
}
