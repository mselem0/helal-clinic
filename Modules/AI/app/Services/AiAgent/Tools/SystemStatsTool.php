<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\Booking\Models\Booking;
use Modules\PatientCore\Models\Patient;
use Modules\PatientCore\Models\VisitRecord;

class SystemStatsTool implements ToolInterface
{
    public function getName(): string
    {
        return 'get_system_stats';
    }

    public function getDescription(): string
    {
        return 'Get total counts of patients, bookings, and visit records in the clinic system.';
    }

    public function getDeclaration(): FunctionDeclaration
    {
        return new FunctionDeclaration(
            name: $this->getName(),
            description: $this->getDescription(),
            parameters: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'type' => new Schema(
                        type: DataType::STRING,
                        description: 'Optional: Specific stat to get. Can be "patients", "bookings", or "visits". If omitted, returns all stats.',
                    ),
                ],
            )
        );
    }

    public function execute(array $input): array
    {
        $type = $input['type'] ?? 'all';

        $stats = [];

        if ($type === 'all' || $type === 'patients') {
            $stats['patients_count'] = Patient::count();
        }

        if ($type === 'all' || $type === 'bookings') {
            $stats['bookings_count'] = Booking::count();
        }

        if ($type === 'all' || $type === 'visits') {
            $stats['visits_count'] = VisitRecord::count();
        }

        return $stats;
    }
}
