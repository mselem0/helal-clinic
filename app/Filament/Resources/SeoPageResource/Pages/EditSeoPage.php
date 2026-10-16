<?php

// app/Filament/Resources/SeoPageResource/Pages/EditSeoPage.php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Resources\SeoPageResource;
use App\Services\SeoScoreCalculator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeoPage extends EditRecord
{
    protected static string $resource = SeoPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $calculator = new SeoScoreCalculator;
        $result = $calculator->calculate($this->record);
        $this->record->update([
            'seo_score' => $result['score'],
            'score_breakdown' => $result['breakdown'],
        ]);
    }
}
