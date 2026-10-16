<?php

namespace App\Livewire\Seo;

use App\Models\SeoPage;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class PagesTab extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        if ($pageId = session('mount_page_id')) {
            $this->dispatch('mountTableAction', name: 'edit', record: $pageId);
        }
    }

    public function render()
    {
        return view('livewire.seo.pages-tab');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SeoPage::query())
            ->columns([
                Tables\Columns\TextColumn::make('page_label')
                    ->label('الصفحة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('page_key')
                    ->label('المفتاح')
                    ->searchable(),
                Tables\Columns\TextColumn::make('seo_score')
                    ->label('درجة SEO')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state.'/100')
                    ->color(fn ($state) => $state > 80 ? 'success' : ($state > 50 ? 'warning' : 'danger'))
                    ->description(fn ($state) => match (true) {
                        $state > 80 => '✅ ممتاز',
                        $state > 50 => '⚠️ يحتاج تحسين',
                        default => '🔴 ضعيف جداً',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تحسين SEO')
                    ->icon('heroicon-o-sparkles')
                    ->modalWidth('7xl')
                    ->form($this->getSeoFormSchema())
                    ->mutateRecordDataUsing(fn (array $data, SeoPage $record) => array_replace($data, $record->draft_data ?? []))
                    ->using(function (SeoPage $record, array $data) {
                        $record->update(['draft_data' => $data]);

                        return $record;
                    })
                    ->successNotificationTitle('تم حفظ مسودة SEO — النشر من استوديو الموقع'),
            ]);
    }

    protected function getSeoFormSchema(): array
    {
        return [
            Forms\Components\Tabs::make('SeoEditorTabs')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('عام')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Section::make('المحتوى الأساسي')
                                        ->columnSpan(2)
                                        ->schema([
                                            Forms\Components\TagsInput::make('focus_keywords')
                                                ->label('الكلمات المفتاحية الأساسية')
                                                ->placeholder('أضف كلمة واضغط Enter')
                                                ->helperText('الكلمات التي تريد أن يظهر موقعك بها في محركات البحث'),

                                            Forms\Components\TextInput::make('meta_title')
                                                ->label('عنوان الميتا (Meta Title)')
                                                ->maxLength(60)
                                                ->live()
                                                ->helperText(fn ($state) => strlen($state ?? '').'/60 حرف - يظهر كعنوان في جوجل'),

                                            Forms\Components\Textarea::make('meta_description')
                                                ->label('وصف الميتا (Meta Description)')
                                                ->maxLength(160)
                                                ->live()
                                                ->rows(3)
                                                ->helperText(fn ($state) => strlen($state ?? '').'/160 حرف - يظهر كوصف تحت العنوان في جوجل'),

                                            Forms\Components\TagsInput::make('meta_keywords')
                                                ->label('كلمات الميتا المفتاحية (Keywords)'),
                                        ]),

                                    Forms\Components\Section::make('معاينة البحث (SERP Preview)')
                                        ->columnSpan(1)
                                        ->schema([
                                            Forms\Components\ViewField::make('serp_preview')
                                                ->view('filament.forms.components.seo-serp-preview'),
                                        ]),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('السوشيال ميديا')
                        ->icon('heroicon-o-share')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Section::make('فيسبوك / واتساب (Open Graph)')
                                        ->schema([
                                            Forms\Components\TextInput::make('og_title')->label('عنوان المنشور'),
                                            Forms\Components\Textarea::make('og_description')->label('وصف المنشور')->rows(2),
                                            Forms\Components\FileUpload::make('og_image')->image()->disk('public')->directory('landing')
                                                ->label('صورة المنشور'),
                                        ]),
                                    Forms\Components\Section::make('تويتر / X')
                                        ->schema([
                                            Forms\Components\TextInput::make('twitter_title')->label('عنوان التغريدة'),
                                            Forms\Components\Textarea::make('twitter_description')->label('وصف التغريدة')->rows(2),
                                            Forms\Components\FileUpload::make('twitter_image')->image()->disk('public')->directory('landing')
                                                ->label('صورة التغريدة'),
                                        ]),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('البيانات المنظمة')->schema([Forms\Components\Placeholder::make('generated_schema')->label('تلقائية')->content('تُبنى بيانات الطبيب والفروع والمقالات والفيديوهات ومسار التنقل من المحتوى الظاهر والبيانات الفعلية المكتملة.')]),
                ])->columnSpanFull(),
        ];
    }
}
