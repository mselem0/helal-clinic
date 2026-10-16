<?php

namespace Modules\AI\Filament\Resources\KnowledgeChunkResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\AI\Filament\Resources\KnowledgeChunkResource;

class ListKnowledgeChunks extends ListRecords
{
    protected static string $resource = KnowledgeChunkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
