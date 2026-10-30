<?php

namespace App\Filament\Resources\SitePageResource\Pages;

use App\Filament\Pages\ThemeEditor;
use App\Filament\Resources\SitePageResource;
use App\Services\ThemeDraftService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

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
        $data = array_replace($record->draft_data ?? [], $data);
        ThemeDraftService::validatePageDraft($record, $data);
        $record->update(['draft_data' => $data]);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ المسودة';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sectionContent')->label('نصوص وصور الصفحة')->color('gray')
                ->url(fn () => ThemeEditor::getUrl(['selectedPageKey' => $this->record->page_key])),
            Action::make('preview')->label('معاينة')->color('gray')
                ->url(fn () => URL::temporarySignedRoute('site.preview', now()->addMinutes(30), ['page' => $this->record->id, 'preview' => 1]))->openUrlInNewTab(),
            Action::make('publish')->label('نشر المسودات')->requiresConfirmation()
                ->modalDescription('سيتم حفظ الصفحة ونشر كل مسودات الموقع المحفوظة.')
                ->action(function (): void {
                    $this->save();
                    app(ThemeDraftService::class)->publish();
                    $this->redirect(SitePageResource::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }
}
