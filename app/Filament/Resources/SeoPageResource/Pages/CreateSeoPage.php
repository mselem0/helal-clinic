<?php

// app/Filament/Resources/SeoPageResource/Pages/CreateSeoPage.php

namespace App\Filament\Resources\SeoPageResource\Pages;

use App\Filament\Resources\SeoPageResource;
use App\Services\SeoScoreCalculator;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoPage extends CreateRecord
{
    protected static string $resource = SeoPageResource::class;

    protected function afterCreate(): void
    {
        $calculator = new SeoScoreCalculator;
        $result = $calculator->calculate($this->record);
        $this->record->update([
            'seo_score' => $result['score'],
            'score_breakdown' => $result['breakdown'],
        ]);
    }
}
