<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\PatientCore\Models\VisitRecord;

class GetVisitRecordsTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_visit_records';
    }

    public function getDescription(): string
    {
        return 'Retrieve a patient\'s medical visit history, including clinical notes, diagnoses, and treatment plans. Crucial for understanding a patient\'s medical condition.';
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
                    'limit' => new Schema(
                        type: DataType::INTEGER,
                        description: 'Number of recent visits to retrieve (default: 5).',
                    ),
                ],
                required: ['patient_id'],
            )
        );
    }

    public function execute(array $input): array
    {
        $patientId = $input['patient_id'];
        $limit = $input['limit'] ?? 5;

        $visits = VisitRecord::where('patient_id', $patientId)
            ->orderBy('visit_date', 'desc')
            ->limit($limit)
            ->get();

        if ($visits->isEmpty()) {
            return [
                'patient_id' => $patientId,
                'has_visits' => false,
                'message' => 'No visit records found for this patient.',
            ];
        }

        return [
            'patient_id' => $patientId,
            'has_visits' => true,
            'total_retrieved' => $visits->count(),
            'visits' => $visits->map(fn ($v) => [
                'id' => $v->id,
                'date' => $v->visit_date?->format('Y-m-d'),
                'chief_complaint' => $v->chief_complaint,
                'diagnosis' => $v->diagnosis,
                'examination_notes' => $v->examination_notes,
                'treatment_plan' => $v->treatment_plan,
                'follow_up_date' => $v->follow_up_date?->format('Y-m-d'),
            ])->toArray(),
        ];
    }
}
