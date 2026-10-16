<?php

namespace Modules\Clinical\Filament\Resources\PrintTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Clinical\Filament\Resources\PrintTemplateResource;

class EditPrintTemplate extends EditRecord
{
    protected static string $resource = PrintTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('معاينة الروشتة')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (): string => route('admin.print-templates.preview', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
