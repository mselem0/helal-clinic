<?php

// app/Filament/Resources/SeoPageResource/Pages/EditSeoPage.php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Resources\SeoPageResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSeoPage extends EditRecord
{
    protected static string $resource = SeoPageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_replace($data, $this->record->draft_data ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update(['draft_data' => $data]);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ مسودة SEO — النشر من استوديو الموقع';
    }
}
