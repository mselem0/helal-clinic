<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;

interface ToolInterface
{
    /**
     * Get the name of the tool.
     */
    public function getName(): string;

    /**
     * Get the description of what the tool does.
     */
    public function getDescription(): string;

    /**
     * Get the Gemini FunctionDeclaration for native tool calling.
     */
    public function getDeclaration(): FunctionDeclaration;

    /**
     * Execute the tool with the given input.
     */
    public function execute(array $input): array;
}
