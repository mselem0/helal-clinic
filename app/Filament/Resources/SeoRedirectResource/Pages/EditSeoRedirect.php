<?php

// app/Filament/Resources/SeoRedirectResource/Pages/EditSeoRedirect.php

namespace App\Filament\Resources\SeoRedirectResource\Pages;

use App\Filament\Resources\SeoRedirectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeoRedirect extends EditRecord
{
    protected static string $resource = SeoRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
