<?php

namespace App\Filament\Resources\SiteNavigationResource\Pages;

use App\Filament\Resources\SiteNavigationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSiteNavigation extends EditRecord
{
    protected static string $resource = SiteNavigationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = array_replace($data, $this->record->draft_data ?? []);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {

        $record->update(['draft_data' => $data]);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ المسودة — النشر من استوديو الموقع';
    }
}
