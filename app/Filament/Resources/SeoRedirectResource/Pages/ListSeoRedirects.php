<?php

// app/Filament/Resources/SeoRedirectResource/Pages/ListSeoRedirects.php

namespace App\Filament\Resources\SeoRedirectResource\Pages;

use App\Filament\Resources\SeoRedirectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeoRedirects extends ListRecords
{
    protected static string $resource = SeoRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
