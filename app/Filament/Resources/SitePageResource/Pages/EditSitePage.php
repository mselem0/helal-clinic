<?php

namespace App\Filament\Resources\SitePageResource\Pages;

use App\Filament\Resources\SitePageResource;
use App\Services\ThemeDraftService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSitePage extends EditRecord
{
    protected static string $resource = SitePageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = array_replace($data, $this->record->draft_data ?? []);
        $data['sections'] = array_map(fn ($s) => is_array($s) ? $s : ['key' => $s], $data['sections'] ?? []);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        ThemeDraftService::validatePageDraft($record, $data);
        $record->update(['draft_data' => $data]);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ المسودة — النشر من استوديو الموقع';
    }
}
