<?php

namespace Modules\AI\Services\AiAgent;

use Gemini\Data\Content;
use Gemini\Data\FunctionResponse;
use Gemini\Data\Part;
use Gemini\Data\Tool;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LlmClient
{
    protected array $priorityModels;

    public function __construct()
    {
        $this->priorityModels = config('ai-agent.google.models_priority', ['gemini-3.5-flash']);
    }

    /**
     * Generate a response with automatic failover and smart selection caching.
     */
    public function generateResponse(string $prompt, array $history = [], array $tools = []): array
    {
        throw_unless(config('clinic-integrations.ai'), \RuntimeException::class, 'تكامل الذكاء الاصطناعي غير مفعّل.');
        // 0. Determine starting point (Last successful model that isn't in cooldown)
        $preferredModel = Cache::get('ai_preferred_model', $this->priorityModels[0]);

        // Re-order list to try preferred first
        $tryModels = array_unique(array_merge([$preferredModel], $this->priorityModels));

        $lastError = null;

        foreach ($tryModels as $modelName) {
            // Check if model is in cooldown
            if (Cache::has("ai_model_cooldown_{$modelName}")) {
                continue;
            }

            try {
                $generativeModel = Gemini::generativeModel($modelName);

                if (! empty($tools)) {
                    $generativeModel = $generativeModel->withTool(
                        new Tool(functionDeclarations: $tools)
                    );
                }

                $chat = $generativeModel->startChat(history: $history);
                $response = $chat->sendMessage($prompt);

                $parts = $response->parts();

                // On success, update preferred model (cache for 1 hour)
                Cache::put('ai_preferred_model', $modelName, 3600);

                // 1. Check for Function Call
                foreach ($parts as $part) {
                    if ($part instanceof Part && isset($part->functionCall)) {
                        return [
                            'type' => 'function_call',
                            'name' => $part->functionCall->name,
                            'args' => $part->functionCall->args,
                            'call_id' => $part->functionCall->id ?? null,
                            'chat_session' => $chat,
                            'active_model' => $this->formatModelName($modelName),
                        ];
                    }
                }

                // 2. Extract Text
                $text = '';
                foreach ($parts as $part) {
                    if (isset($part->thought) && $part->thought === true) {
                        continue;
                    }
                    if (isset($part->text)) {
                        $text .= $part->text;
                    }
                }

                return [
                    'type' => 'text',
                    'text' => trim($text),
                    'active_model' => $this->formatModelName($modelName),
                ];

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();

                if ($this->isRateLimitError($errorMessage)) {
                    // Put model on cooldown for 60 seconds
                    Cache::put("ai_model_cooldown_{$modelName}", true, 60);
                    Log::warning("AI Model {$modelName} hit limit. Cooldown activated.");
                    $lastError = $errorMessage;

                    continue;
                }

                Log::error("AI Agent LLM Critical Error ({$modelName}): ".$errorMessage);
                break;
            }
        }

        return [
            'type' => 'error',
            'text' => null,
            'last_error' => $lastError,
        ];
    }

    /**
     * Clean up model name for UI display.
     */
    protected function formatModelName(string $id): string
    {
        return str_replace(['models/', '-it', '-a4b'], '', $id);
    }

    /**
     * Reply to a function call and get the final text response.
     */
    public function replyToFunctionCall($chatSession, string $functionName, array $functionResponse, ?string $callId = null): ?string
    {
        try {
            $response = $chatSession->sendMessage(
                new Content(
                    parts: [
                        new Part(
                            functionResponse: new FunctionResponse(
                                name: $functionName,
                                response: $functionResponse,
                                id: $callId
                            )
                        ),
                    ],
                    role: Role::USER
                )
            );

            $text = '';
            foreach ($response->parts() as $part) {
                if (isset($part->thought) && $part->thought === true) {
                    continue;
                }

                if (isset($part->text)) {
                    $text .= $part->text;
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::error('AI Agent Function Reply Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Determine if an error is related to rate limits or high demand.
     */
    protected function isRateLimitError(string $message): bool
    {
        $indicators = [
            '429',
            'Resource has been exhausted',
            'rate limit',
            'high demand',
            'quota',
            'overloaded',
            'too many requests',
        ];

        foreach ($indicators as $indicator) {
            if (stripos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }
}
