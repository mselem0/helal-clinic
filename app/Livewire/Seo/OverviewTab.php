<?php

namespace App\Livewire\Seo;

use App\Helpers\SeoHelper;
use App\Models\Seo404Monitor;
use App\Models\SeoPage;
use App\Models\SeoRedirect;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class OverviewTab extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function render()
    {
        return view('livewire.seo.overview-tab', [
            'stats' => $this->getStats(),
            'healthIssues' => $this->getHealthIssues(),
        ]);
    }

    protected function getHealthIssues(): array
    {
        $issues = [];

        // Check pages without description
        $noDescCount = SeoPage::whereNull('meta_description')->orWhere('meta_description', '')->count();
        if ($noDescCount > 0) {
            $issues[] = ['type' => 'error', 'message' => "يوجد $noDescCount صفحات بدون وصف ميتا (Meta Description)."];
        }

        // Check low SEO score pages
        $lowScoreCount = SeoPage::where('seo_score', '<', 50)->count();
        if ($lowScoreCount > 0) {
            $issues[] = ['type' => 'warning', 'message' => "يوجد $lowScoreCount صفحات لديها درجة SEO أقل من 50."];
        }

        // Check active 404s
        $active404Count = Seo404Monitor::where('is_resolved', false)->count();
        if ($active404Count > 0) {
            $issues[] = ['type' => 'error', 'message' => "تم اكتشاف $active404Count روابط مكسورة (404) تحتاج لمعالجة."];
        }

        // Check sitemap
        $sitemapEnabled = SeoHelper::getSetting('sitemap_enabled');
        if (! $sitemapEnabled) {
            $issues[] = ['type' => 'warning', 'message' => 'خريطة الموقع (Sitemap) غير مفعلة حالياً.'];
        }

        return $issues;
    }

    protected function getStats(): array
    {
        return [
            'avg_score' => round(SeoPage::avg('seo_score') ?? 0),
            'indexed_pages' => SeoPage::where('robots', 'index_follow')->count(),
            'active_404' => Seo404Monitor::where('is_resolved', false)->count(),
            'redirects' => SeoRedirect::where('is_active', true)->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SeoPage::query())
            ->columns([
                Tables\Columns\TextColumn::make('page_label')
                    ->label('الصفحة'),
                Tables\Columns\TextColumn::make('seo_score')
                    ->label('درجة SEO')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => $state > 80 ? 'success' : ($state > 50 ? 'warning' : 'danger')),
                Tables\Columns\IconColumn::make('robots')
                    ->label('مؤرشف؟')
                    ->icon(fn ($state) => $state === 'index_follow' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state) => $state === 'index_follow' ? 'success' : 'danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('تحسين')
                    ->icon('heroicon-o-pencil-square')
                    ->action(fn (SeoPage $record) => $this->dispatch('switch-tab', tab: 'pages', pageId: $record->id)),
            ]);
    }
}
