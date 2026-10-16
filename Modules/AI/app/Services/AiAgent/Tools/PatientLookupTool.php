<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Carbon\Carbon;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\PatientCore\Models\Patient;

class PatientLookupTool implements ToolInterface
{
    public function getName(): string
    {
        return 'patient_lookup';
    }

    public function getDescription(): string
    {
        return 'Search for a patient by name or phone number and return their basic profile.';
    }

    public function getDeclaration(): FunctionDeclaration
    {
        return new FunctionDeclaration(
            name: $this->getName(),
            description: $this->getDescription(),
            parameters: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'query' => new Schema(
                        type: DataType::STRING,
                        description: 'The name or phone number of the patient',
                    ),
                    'search_by' => new Schema(
                        type: DataType::STRING,
                        description: 'optional: "name" or "phone"',
                    ),
                ],
                required: ['query'],
            )
        );
    }

    public function execute(array $input): array
    {
        $query = $input['query'] ?? null;
        if (! $query) {
            return ['error' => 'No search query provided.'];
        }

        $searchBy = $input['search_by'] ?? null;

        $patients = Patient::query()
            ->when($searchBy === 'phone', fn ($q) => $q->where('phone', 'like', "%$query%"))
            ->when($searchBy === 'name', fn ($q) => $q->where('name', 'like', "%$query%"))
            ->when(! $searchBy, fn ($q) => $q->where('name', 'like', "%$query%")->orWhere('phone', 'like', "%$query%"))
            ->limit(5)
            ->get();

        if ($patients->isEmpty()) {
            return ['found' => false, 'message' => 'No patients found matching your query.'];
        }

        return [
            'found' => true,
            'count' => $patients->count(),
            'patients' => $patients->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'phone' => $p->phone,
                'age' => $p->date_of_birth ? Carbon::parse($p->date_of_birth)->age : 'Unknown',
                'blood_type' => $p->blood_type,
                'last_visit' => $p->created_at->toFormattedDateString(),
            ])->toArray(),
        ];
    }
}
