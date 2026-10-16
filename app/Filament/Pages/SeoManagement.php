<?php

namespace App\Filament\Pages;

use App\Models\Seo404Monitor;
use App\Models\SeoPage;
use App\Models\SeoRedirect;
use App\Support\AuthorizesSitePage;
use App\Traits\HasAnalyticsActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SeoManagement extends Page implements HasActions
{
    use AuthorizesSitePage;
    use HasAnalyticsActions;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $title = 'إدارة SEO';

    protected static ?string $navigationLabel = 'إدارة SEO';

    protected static ?int $navigationSort = 90;

    protected function getHeaderActions(): array
    {
        return [
            $this->connectAction()
                ->label('إعدادات الربط والتتبع')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->outlined(),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('seo_nav_badge_404', 300, function () {
            return (string) Seo404Monitor::where('is_resolved', false)->count() ?: null;
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected static string $view = 'filament.pages.seo-management';

    public string $activeTab = 'overview';

    protected $listeners = [
        'switch-tab' => 'handleSwitchTab',
    ];

    public function getStats(): array
    {
        return Cache::remember('seo_management_stats', 300, function () {
            $avgScore = round(SeoPage::avg('seo_score') ?? 0);

            return [
                'avg_score' => $avgScore,
                'page_count' => SeoPage::count(),
                'active_404' => Seo404Monitor::where('is_resolved', false)->count(),
                'redirects' => SeoRedirect::where('is_active', true)->count(),
                'score_color' => $avgScore >= 80 ? '#10b981' : ($avgScore >= 50 ? '#f59e0b' : '#ef4444'),
            ];
        });
    }

    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'overview');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function handleSwitchTab(string $tab, ?int $pageId = null): void
    {
        $this->activeTab = $tab;
        if ($pageId) {
            session()->flash('mount_page_id', $pageId);
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
