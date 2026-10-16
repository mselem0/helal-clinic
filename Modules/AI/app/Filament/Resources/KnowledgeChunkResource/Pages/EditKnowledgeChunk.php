<?php

namespace Modules\AI\Filament\Resources\KnowledgeChunkResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\AI\Filament\Resources\KnowledgeChunkResource;
use Modules\AI\Services\AiAgent\EmbeddingService;

class EditKnowledgeChunk extends EditRecord
{
    protected static string $resource = KnowledgeChunkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Regenerate embedding if content changed
        if ($this->record->content !== $data['content']) {
            $embeddingService = app(EmbeddingService::class);
            $data['embedding'] = $embeddingService->embed($data['content']);
        }

        return $data;
    }
}
