<?php

namespace Modules\AI\Services\AiAgent;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentIngestionService
{
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Extract text from a file (PDF, Word, or Text).
     */
    public function extractText(string $filePath, string $mimeType): ?string
    {
        try {
            if (str_contains($mimeType, 'pdf')) {
                return (new PdfParser)->parseFile($filePath)->getText();
            }

            if (str_contains($mimeType, 'word') || str_contains($mimeType, 'officedocument')) {
                $phpWord = WordIOFactory::load($filePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText()."\n";
                        }
                    }
                }

                return $text;
            }

            if (str_contains($mimeType, 'text')) {
                return file_get_contents($filePath);
            }
        } catch (\Exception $e) {
            Log::error('AI Document Extraction Error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Chunk text into smaller pieces.
     */
    public function chunkText(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $words = explode(' ', $text);
        $chunks = [];
        $currentChunk = [];
        $wordCount = 0;

        foreach ($words as $word) {
            $currentChunk[] = $word;
            $wordCount++;

            if ($wordCount >= $chunkSize) {
                $chunks[] = implode(' ', $currentChunk);
                // Keep the overlap
                $currentChunk = array_slice($currentChunk, -$overlap);
                $wordCount = count($currentChunk);
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = implode(' ', $currentChunk);
        }

        return $chunks;
    }
}
