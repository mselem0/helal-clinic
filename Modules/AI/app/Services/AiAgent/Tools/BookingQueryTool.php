<?php

namespace Modules\AI\Services\AiAgent\Tools;

use Carbon\Carbon;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Modules\Booking\Models\Booking;

class BookingQueryTool implements ToolInterface
{
    public function getName(): string
    {
        return 'booking_query';
    }

    public function getDescription(): string
    {
        return 'Query appointments/bookings by date, status, or patient name.';
    }

    public function getDeclaration(): FunctionDeclaration
    {
        return new FunctionDeclaration(
            name: $this->getName(),
            description: $this->getDescription(),
            parameters: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'date' => new Schema(
                        type: DataType::STRING,
                        description: 'optional: YYYY-MM-DD, "today", "tomorrow"',
                    ),
                    'status' => new Schema(
                        type: DataType::STRING,
                        description: 'optional: "pending", "confirmed", "cancelled", "completed"',
                    ),
                    'patient_name' => new Schema(
                        type: DataType::STRING,
                        description: 'optional',
                    ),
                ]
            )
        );
    }

    public function execute(array $input): array
    {
        $query = Booking::query()->with('service');

        if (isset($input['date'])) {
            $date = $input['date'];
            if ($date === 'today') {
                $date = Carbon::today()->toDateString();
            }
            if ($date === 'tomorrow') {
                $date = Carbon::tomorrow()->toDateString();
            }
            $query->whereDate('booking_date', $date);
        }

        if (isset($input['status'])) {
            $query->where('status', $input['status']);
        }

        if (isset($input['patient_name'])) {
            $query->where('patient_name', 'like', '%'.$input['patient_name'].'%');
        }

        $bookings = $query->limit(10)->get();

        return [
            'count' => $bookings->count(),
            'bookings' => $bookings->map(fn ($b) => [
                'id' => $b->id,
                'patient' => $b->patient_name,
                'date' => $b->booking_date,
                'time' => $b->booking_time,
                'service' => $b->service?->name,
                'status' => $b->status,
            ])->toArray(),
        ];
    }
}
