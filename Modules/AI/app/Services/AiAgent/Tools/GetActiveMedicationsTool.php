<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\Clinical\Models\PatientMedication;

class GetActiveMedicationsTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_active_medications';
    }

    public function getDescription(): string
    {
        return 'Get the patient\'s currently active medications with dosages and frequencies. Essential for checking drug interactions or reviewing current treatment.';
    }

    public function getDeclaration(): FunctionDeclaration
    {
        return new FunctionDeclaration(
            name: $this->getName(),
            description: $this->getDescription(),
            parameters: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'patient_id' => new Schema(
                        type: DataType::INTEGER,
                        description: 'The unique numeric ID of the patient.',
                    ),
                    'include_past' => new Schema(
                        type: DataType::BOOLEAN,
                        description: 'Also include discontinued medications (default false).',
                    ),
                ],
                required: ['patient_id'],
            )
        );
    }

    public function execute(array $input): array
    {
        $patientId = $input['patient_id'];
        $includePast = $input['include_past'] ?? false;

        $query = PatientMedication::where('patient_id', $patientId);

        if (! $includePast) {
            $query->where('is_active', true);
        }

        $medications = $query->orderBy('start_date', 'desc')->get();

        if ($medications->isEmpty()) {
            return [
                'patient_id' => $patientId,
                'has_medications' => false,
                'message' => 'No active medications found for this patient.',
            ];
        }

        return [
            'patient_id' => $patientId,
            'has_medications' => true,
            'total' => $medications->count(),
            'medications' => $medications->map(fn ($m) => [
                'id' => $m->id,
                'medication_name' => $m->medication_name,
                'dosage' => $m->dosage,
                'frequency' => $m->frequency,
                'start_date' => $m->start_date?->format('Y-m-d'),
                'end_date' => $m->end_date?->format('Y-m-d'),
                'is_active' => $m->is_active,
                'notes' => $m->notes,
            ])->toArray(),
        ];
    }
}
