<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\PatientCore\Models\Patient;

class AdvancedPatientSearchTool implements ToolInterface
{
    public function getName(): string
    {
        return 'search_patients';
    }

    public function getDescription(): string
    {
        return 'Search for patients by name, phone, or email. If query is omitted, it returns a general list of patients which can be sorted (e.g., oldest/first patient, or newest).';
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
                        description: 'Optional: The search term (name, phone, or email). Leave empty to get a general list.',
                    ),
                    'limit' => new Schema(
                        type: DataType::INTEGER,
                        description: 'Maximum number of results to return (default: 5).',
                    ),
                    'sort_by' => new Schema(
                        type: DataType::STRING,
                        description: 'Optional: Sort order, e.g., "oldest" (for first patient) or "newest" (default).',
                    ),
                ],
            )
        );
    }

    public function execute(array $input): array
    {
        $queryText = $input['query'] ?? null;
        $limit = $input['limit'] ?? 5;
        $sortBy = $input['sort_by'] ?? 'newest';

        $query = Patient::query();

        if ($queryText) {
            $query->where(function ($q) use ($queryText) {
                $q->where('name', 'like', "%{$queryText}%")
                    ->orWhere('phone', 'like', "%{$queryText}%")
                    ->orWhere('email', 'like', "%{$queryText}%");
            });
        }

        if ($sortBy === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $patients = $query->limit($limit)->get();

        if ($patients->isEmpty()) {
            return ['found' => false, 'message' => 'No patients found matching the query.'];
        }

        return [
            'found' => true,
            'count' => $patients->count(),
            'patients' => $patients->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'phone' => $p->phone,
                'email' => $p->email,
                'gender' => $p->gender,
                'blood_type' => $p->blood_type,
                'last_visit' => $p->last_visit?->visit_date?->format('Y-m-d'),
            ])->toArray(),
        ];
    }
}
