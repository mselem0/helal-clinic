<?php

namespace App\Filament\Resources\SiteNavigationResource\Pages;

use App\Filament\Resources\SiteNavigationResource;
use Filament\Resources\Pages\ListRecords;

class ListSiteNavigations extends ListRecords
{
    protected static string $resource = SiteNavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
