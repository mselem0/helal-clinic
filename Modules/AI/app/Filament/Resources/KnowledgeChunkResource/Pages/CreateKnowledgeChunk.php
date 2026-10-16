<?php

namespace Modules\AI\Filament\Resources\KnowledgeChunkResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\AI\Filament\Resources\KnowledgeChunkResource;
use Modules\AI\Services\AiAgent\EmbeddingService;

class CreateKnowledgeChunk extends CreateRecord
{
    protected static string $resource = KnowledgeChunkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate embedding before saving
        $embeddingService = app(EmbeddingService::class);
        $data['embedding'] = $embeddingService->embed($data['content']);

        return $data;
    }
}
