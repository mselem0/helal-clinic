<?php

namespace Modules\AI\Services\AiAgent;

use Illuminate\Support\Facades\Log;
use Modules\AI\Services\AiAgent\Tools\AdvancedPatientSearchTool;
use Modules\AI\Services\AiAgent\Tools\BookingQueryTool;
use Modules\AI\Services\AiAgent\Tools\GetActiveMedicationsTool;
use Modules\AI\Services\AiAgent\Tools\GetVisitRecordsTool;
use Modules\AI\Services\AiAgent\Tools\PatientFullProfileTool;
use Modules\AI\Services\AiAgent\Tools\PatientLookupTool;
use Modules\AI\Services\AiAgent\Tools\SystemStatsTool;

class AgentOrchestrator
{
    protected array $tools = [];

    public function __construct(
        protected LlmClient $llm,
        protected ContextBuilder $contextBuilder,
        protected RagEngine $ragEngine
    ) {
        $this->registerTool(new SystemStatsTool);
        $this->registerTool(new AdvancedPatientSearchTool);
        $this->registerTool(new PatientLookupTool);
        $this->registerTool(new BookingQueryTool);
        $this->registerTool(new PatientFullProfileTool);
        $this->registerTool(new GetVisitRecordsTool);
        $this->registerTool(new GetActiveMedicationsTool);
    }

    public function registerTool($tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * The main entry point for the AI Agent to process a request.
     */
    public function handle(string $query, array $history = []): array
    {
        try {
            // 1. Semantic Search (Static Knowledge Only)
            $ragResults = $this->ragEngine->search($query);

            // 2. Build Context
            $context = $this->contextBuilder->build($query, [], $history, $ragResults);

            // 3. Prepare Tools for Gemini
            $toolDeclarations = array_map(fn ($tool) => $tool->getDeclaration(), array_values($this->tools));

            // 4. Initial LLM Call (might return text or a tool call)
            $llmResponse = $this->llm->generateResponse($context, $history, $toolDeclarations);

            $toolName = null;
            $toolResults = [];
            $finalAnswer = '';
            Log::info($llmResponse);
            // 5. Tool Calling Loop
            if ($llmResponse['type'] === 'function_call') {
                $toolName = $llmResponse['name'];
                $args = $llmResponse['args'] ?? [];
                $callId = $llmResponse['call_id'] ?? null;

                if (isset($this->tools[$toolName])) {
                    // Execute Local PHP Tool
                    $toolResults = $this->tools[$toolName]->execute($args);

                    // Reply to Gemini with the results to get the final text
                    $finalAnswer = $this->llm->replyToFunctionCall(
                        $llmResponse['chat_session'],
                        $toolName,
                        $toolResults,
                        $callId
                    );
                } else {
                    $finalAnswer = "I tried to use a tool named '{$toolName}' but it doesn't exist.";
                }
            } elseif ($llmResponse['type'] === 'text') {
                $finalAnswer = $llmResponse['text'];
            } else {
                $finalAnswer = 'عذراً، حدث خطأ غير متوقع.';
            }

            return [
                'answer' => $finalAnswer,
                'tool_used' => $toolName,
                'tool_results' => $toolResults,
                'rag_used' => ! empty($ragResults),
                'sources' => array_map(fn ($r) => $r['source_name'], $ragResults),
                'active_model' => $llmResponse['active_model'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Agent Orchestrator Error: '.$e->getMessage());

            return [
                'answer' => 'عذراً دكتور، حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
