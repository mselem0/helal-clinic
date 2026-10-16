<?php

// app/Filament/Widgets/AnalyticsStatusWidget.php

namespace App\Filament\Widgets;

use App\Helpers\SeoHelper;
use App\Services\GoogleAnalyticsService;
use App\Traits\HasAnalyticsActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class AnalyticsStatusWidget extends Widget implements HasActions, HasForms
{
    use HasAnalyticsActions;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.analytics-status-widget';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function getAnalyticsData(): array
    {
        $gaService = app(GoogleAnalyticsService::class);
        $traffic = $gaService->getTrafficOverview();

        $measurementId = SeoHelper::getSetting('google_analytics_id');
        $gaApiConnected = $gaService->isConnected();

        return [
            'google' => [
                'id' => $measurementId,
                'status' => $gaApiConnected || ! empty($measurementId),
                'label' => 'Google Analytics (GA4)',
                'live_data' => $gaApiConnected
                    ? $traffic['sessions'].' جلسة اليوم'
                    : ($measurementId ? 'التتبع نشط (بدون API)' : 'بانتظار الإعداد'),
            ],
            'facebook' => [
                'id' => SeoHelper::getSetting('facebook_pixel_id'),
                'status' => ! empty(SeoHelper::getSetting('facebook_pixel_id')),
                'label' => 'Facebook Pixel',
                'live_data' => ! empty(SeoHelper::getSetting('facebook_pixel_id')) ? 'تتبع البيكسل نشط' : null,
            ],
            'gtm' => [
                'id' => SeoHelper::getSetting('gtm_id'),
                'status' => ! empty(SeoHelper::getSetting('gtm_id')),
                'label' => 'Google Tag Manager',
                'live_data' => ! empty(SeoHelper::getSetting('gtm_id')) ? 'مدير العلامات نشط' : null,
            ],
        ];
    }
}
