<?php

namespace Modules\AI\Services\AiAgent;

use Modules\AI\Models\AiEmbedding;
use Modules\AI\Models\KnowledgeChunk;

class RagEngine
{
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Search for the most relevant knowledge chunks and system embeddings for a given query.
     */
    public function search(string $query, ?int $topK = null): array
    {
        $topK = $topK ?? config('ai-agent.rag.top_k', 10);
        $threshold = config('ai-agent.rag.similarity_threshold', 0.5);

        $queryVector = $this->embeddingService->embed($query);
        if (! $queryVector) {
            return [];
        }

        $results = [];

        // 1. Search Static Knowledge Base (KnowledgeChunk)
        $knowledgeChunks = KnowledgeChunk::where('is_active', true)->get();
        foreach ($knowledgeChunks as $chunk) {
            if (! $chunk->embedding) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $chunk->embedding);

            if ($similarity >= $threshold) {
                $results[] = [
                    'source_name' => $chunk->source_name,
                    'content' => $chunk->content,
                    'type' => 'knowledge',
                    'similarity' => $similarity,
                ];
            }
        }

        // 2. Search Dynamic System Memory (AiEmbedding)
        $systemEmbeddings = AiEmbedding::where('status', 'synced')->get();
        foreach ($systemEmbeddings as $embedding) {
            if (! $embedding->vector) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryVector, $embedding->vector);

            if ($similarity >= $threshold) {
                $results[] = [
                    'source_name' => 'System Record: '.class_basename($embedding->embeddable_type),
                    'content' => $embedding->text_representation,
                    'type' => 'system_data',
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity descending
        usort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return array_slice($results, 0, $topK);
    }

    /**
     * Calculate Cosine Similarity between two vectors.
     */
    protected function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        foreach ($vec1 as $i => $val) {
            $dotProduct += $val * $vec2[$i];
            $normA += $val * $val;
            $normB += $vec2[$i] * $vec2[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
