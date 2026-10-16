<?php

namespace Modules\AI\Services\AiAgent;

class ContextBuilder
{
    /**
     * Build a rich context string from tool results, conversation history, and RAG data.
     */
    public function build(string $query, array $toolResults = [], array $history = [], array $ragResults = []): string
    {
        $context = "User Question: \"{$query}\"\n\n";

        if (! empty($ragResults)) {
            $context .= "Retrieved Information from Knowledge Base & System Memory:\n";
            foreach ($ragResults as $result) {
                $type = $result['type'] === 'system_data' ? '[System Data]' : '[Medical Knowledge]';
                $context .= "- {$type} Source: {$result['source_name']}: {$result['content']}\n";
            }
            $context .= "\n";
        }

        if (! empty($toolResults)) {
            $context .= "Retrieved Structured Data (Real-time DB):\n";
            $context .= json_encode($toolResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\n";
        }

        $context .= "Guidelines:\n";
        $context .= "- Answer in Arabic (اللغة العربية).\n";
        $context .= "- Be professional, concise, and medical.\n";
        $context .= "- Use the provided System Data and Medical Knowledge to give precise answers about the clinic and patients.\n";
        $context .= "- If no data was found, state clearly that you don't have information on this specific query in the current records.\n";

        return $context;
    }
}
