<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\PatientCore\Models\Patient;

class PatientFullProfileTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_patient_full_profile';
    }

    public function getDescription(): string
    {
        return 'Get the complete and detailed profile of a specific patient, including demographics, contact info, and medical notes. Use this after finding a patient ID via search_patients to get full context.';
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
                ],
                required: ['patient_id'],
            )
        );
    }

    public function execute(array $input): array
    {
        $patientId = $input['patient_id'];

        $patient = Patient::with(['bookings' => function ($q) {
            $q->latest('booking_date')->limit(3);
        }])->find($patientId);

        if (! $patient) {
            return ['found' => false, 'message' => "Patient with ID {$patientId} not found."];
        }

        return [
            'found' => true,
            'id' => $patient->id,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'gender' => $patient->gender,
            'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
            'age' => $patient->age,
            'blood_type' => $patient->blood_type,
            'national_id' => $patient->national_id,
            'address' => $patient->address,
            'emergency_contact' => $patient->emergency_contact_name.' ('.$patient->emergency_contact_phone.')',
            'medical_notes' => $patient->notes,
            'registration_date' => $patient->created_at?->format('Y-m-d'),
            'recent_bookings' => $patient->bookings->map(fn ($b) => [
                'date' => $b->booking_date?->format('Y-m-d'),
                'status' => $b->status,
                'condition' => $b->patient_condition,
            ])->toArray(),
        ];
    }
}
