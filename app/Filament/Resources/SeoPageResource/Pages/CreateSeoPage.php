<?php

// app/Filament/Resources/SeoPageResource/Pages/CreateSeoPage.php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Resources\SeoPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoPage extends CreateRecord
{
    protected static string $resource = SeoPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ['page_key' => $data['page_key'], 'page_label' => $data['page_label'], 'draft_data' => $data, 'robots' => 'index_follow'];
    }
}
