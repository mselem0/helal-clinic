<?php

namespace App\Livewire\Seo;

use App\Services\GoogleAnalyticsService;
use App\Traits\HasAnalyticsActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class AnalyticsWidget extends Component implements HasActions, HasForms
{
    use HasAnalyticsActions;
    use InteractsWithActions;
    use InteractsWithForms;

    protected $listeners = [
        'open-connect-modal' => 'openConnectModal',
    ];

    public function openConnectModal(): void
    {
        $this->mountAction('connect');
    }

    public function render()
    {
        return view('livewire.seo.analytics-widget', [
            'stats' => $this->getStats(),
        ]);
    }

    public function getStats(): array
    {
        $service = app(GoogleAnalyticsService::class);

        if (! $service->isConnected()) {
            return ['connected' => false];
        }

        $traffic = $service->getTrafficOverview();
        $realtime = $service->getRealtimeActiveUsers();
        $trend = $service->getDailySessionsLast7Days();

        return [
            'connected' => true,
            'sessions_today' => $traffic['sessions'],
            'sessions_yesterday' => $traffic['sessions_yesterday'],
            'realtime_users' => $realtime,
            'change' => $this->calculateChange($traffic['sessions'], $traffic['sessions_yesterday']),
            'trend' => $trend,
        ];
    }

    protected function calculateChange($today, $yesterday): ?float
    {
        if ($yesterday == 0) {
            return null;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }
}
