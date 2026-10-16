<?php

namespace Tests\Unit;

use App\Support\ClinicFeatures;
use Modules\AI\Services\AiAgent\LlmClient;
use Tests\TestCase;

class AiAgentInfrastructureTest extends TestCase
{
    public function test_ai_module_is_optional_and_cannot_make_unconfigured_requests(): void
    {
        if (! class_exists(LlmClient::class)) {
            $this->assertFalse(ClinicFeatures::enabled('AI'));

            return;
        }
        config(['clinic-integrations.ai' => false]);
        $this->expectException(\RuntimeException::class);
        app(LlmClient::class)->generateResponse('test');
    }
}
