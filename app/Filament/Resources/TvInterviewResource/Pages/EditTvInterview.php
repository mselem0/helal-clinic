<?php

namespace App\Filament\Resources\TvInterviewResource\Pages;

use App\Filament\Resources\TvInterviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTvInterview extends EditRecord
{
    protected static string $resource = TvInterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
