<?php

namespace Modules\ActivityLog\Filament\Resources\ActivityLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\ActivityLog\Filament\Resources\ActivityLogResource;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function applySearchToTableQuery(Builder $query): Builder
    {
        $search = $this->getTableSearch();

        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('description', 'like', "%{$search}%")
                ->orWhere('properties', 'like', "%{$search}%")
                ->orWhereHasMorph('subject', '*', function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
