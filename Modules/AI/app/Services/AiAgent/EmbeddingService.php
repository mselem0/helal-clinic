<?php

namespace Modules\AI\Services\AiAgent;

use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected string $model;

    public function __construct()
    {
        $this->model = config('ai-agent.google.embedding_model', 'gemini-embedding-001');
    }

    /**
     * Generate an embedding vector for a given text.
     */
    public function embed(string $text): ?array
    {
        throw_unless(config('clinic-integrations.ai'), \RuntimeException::class, 'تكامل الذكاء الاصطناعي غير مفعّل.');
        try {
            $response = Gemini::embeddingModel($this->model)
                ->embedContent($text);

            return $response->embedding->values;
        } catch (\Exception $e) {
            Log::error('AI Agent Embedding Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Generate embeddings for a batch of texts.
     */
    public function embedBatch(array $texts): array
    {
        $embeddings = [];
        foreach ($texts as $text) {
            $embeddings[] = $this->embed($text);
        }

        return $embeddings;
    }
}
