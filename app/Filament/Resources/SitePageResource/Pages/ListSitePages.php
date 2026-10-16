<?php

namespace App\Filament\Resources\SitePageResource\Pages;

use App\Filament\Resources\SitePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSitePages extends ListRecords
{
    protected static string $resource = SitePageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
