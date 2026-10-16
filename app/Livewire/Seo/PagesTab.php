<?php

namespace App\Livewire\Seo;

use App\Models\SeoPage;
use App\Services\SeoScoreCalculator;
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
                    ->after(function (SeoPage $record) {
                        $calculator = new SeoScoreCalculator;
                        $result = $calculator->calculate($record);
                        $record->update([
                            'seo_score' => $result['score'],
                            'score_breakdown' => $result['breakdown'],
                        ]);
                    }),
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
                                            Forms\Components\SpatieMediaLibraryFileUpload::make('og_image')
                                                ->label('صورة المنشور')
                                                ->collection('seo-page-og'),
                                        ]),
                                    Forms\Components\Section::make('تويتر / X')
                                        ->schema([
                                            Forms\Components\TextInput::make('twitter_title')->label('عنوان التغريدة'),
                                            Forms\Components\Textarea::make('twitter_description')->label('وصف التغريدة')->rows(2),
                                            Forms\Components\SpatieMediaLibraryFileUpload::make('twitter_image')
                                                ->label('صورة التغريدة')
                                                ->collection('seo-page-twitter'),
                                        ]),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Schema Markup')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Forms\Components\Repeater::make('schemas')
                                ->relationship('schemas')
                                ->label('Schema Structured Data')
                                ->addActionLabel('إضافة Schema جديد')
                                ->extraItemActions([
                                    Forms\Components\Actions\Action::make('test_google')
                                        ->label('اختبر في Google')
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->color('info')
                                        ->url('https://search.google.com/test/rich-results', true),
                                ])
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Select::make('schema_type')
                                                ->label('نوع Schema')
                                                ->options([
                                                    'Physician' => '🩺 طبيب (Physician)',
                                                    'FAQPage' => '❓ أسئلة شائعة (FAQPage)',
                                                    'VideoObject' => '🎬 فيديو (VideoObject)',
                                                    'Custom' => '⚙️ مخصص (JSON مباشر)',
                                                ])
                                                ->required()
                                                ->live()
                                                ->helperText('اختر النوع المناسب وستظهر الحقول تلقائياً'),
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('نشط')
                                                ->default(true)
                                                ->inline(false),
                                            Forms\Components\Toggle::make('is_primary')
                                                ->label('أساسي')
                                                ->default(false)
                                                ->inline(false),
                                        ]),

                                    // ── Physician Fields ─────────────────────────────
                                    Forms\Components\Section::make('بيانات الطبيب')
                                        ->schema([
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\TextInput::make('schema_data.name')
                                                    ->label('الاسم الكامل للطبيب')
                                                    ->placeholder('د. محمد هلال')
                                                    ->required(),
                                                Forms\Components\TextInput::make('schema_data.telephone')
                                                    ->label('رقم الهاتف')
                                                    ->tel()
                                                    ->placeholder('+20 12 3456 7890'),
                                                Forms\Components\TextInput::make('schema_data.url')
                                                    ->label('رابط صفحة الطبيب')
                                                    ->url()
                                                    ->placeholder(url('/')),
                                                Forms\Components\Select::make('schema_data.medicalSpecialty')
                                                    ->label('التخصص الطبي')
                                                    ->options([
                                                        'Cardiology' => 'قلب وأوعية دموية',
                                                        'Dermatology' => 'جلدية',
                                                        'Neurology' => 'أعصاب',
                                                        'Oncology' => 'أورام',
                                                        'Orthopedic' => 'عظام',
                                                        'Pediatrics' => 'أطفال',
                                                        'Psychiatry' => 'طب نفسي',
                                                        'Radiology' => 'أشعة',
                                                        'Surgery' => 'جراحة عامة',
                                                        'PlasticSurgery' => 'جراحة تجميلية',
                                                        'Urology' => 'بولية',
                                                        'OphthalmicSurgery' => 'عيون',
                                                        'Gastroenterology' => 'جهاز هضمي',
                                                        'Pulmonology' => 'صدرية',
                                                        'Endocrinology' => 'غدد صماء وسكر',
                                                    ])
                                                    ->searchable(),
                                                Forms\Components\Textarea::make('schema_data.description')
                                                    ->label('الوصف')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                        ])
                                        ->visible(fn (Forms\Get $get) => $get('schema_type') === 'Physician')
                                        ->collapsible(),

                                    // ── FAQPage Fields ───────────────────────────────
                                    Forms\Components\Section::make('الأسئلة الشائعة')
                                        ->schema([
                                            Forms\Components\Repeater::make('schema_data.mainEntity')
                                                ->label('الأسئلة والأجوبة')
                                                ->addActionLabel('إضافة سؤال')
                                                ->schema([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('السؤال')
                                                        ->required()
                                                        ->placeholder('ما هو تخصص الطبيب؟'),
                                                    Forms\Components\Textarea::make('acceptedAnswer.text')
                                                        ->label('الإجابة')
                                                        ->required()
                                                        ->rows(2)
                                                        ->placeholder('إجابة واضحة ومختصرة…'),
                                                ])
                                                ->defaultItems(1)
                                                ->columns(1),
                                        ])
                                        ->visible(fn (Forms\Get $get) => $get('schema_type') === 'FAQPage')
                                        ->collapsible(),

                                    // ── VideoObject Fields ───────────────────────────
                                    Forms\Components\Section::make('بيانات الفيديو')
                                        ->schema([
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\TextInput::make('schema_data.name')
                                                    ->label('عنوان الفيديو')
                                                    ->required(),
                                                Forms\Components\TextInput::make('schema_data.thumbnailUrl')
                                                    ->label('رابط صورة مصغرة')
                                                    ->url(),
                                                Forms\Components\TextInput::make('schema_data.contentUrl')
                                                    ->label('رابط الفيديو')
                                                    ->url(),
                                                Forms\Components\TextInput::make('schema_data.uploadDate')
                                                    ->label('تاريخ الرفع (YYYY-MM-DD)')
                                                    ->placeholder(now()->format('Y-m-d')),
                                                Forms\Components\Textarea::make('schema_data.description')
                                                    ->label('وصف الفيديو')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                        ])
                                        ->visible(fn (Forms\Get $get) => $get('schema_type') === 'VideoObject')
                                        ->collapsible(),

                                    // ── Custom JSON ──────────────────────────────────
                                    Forms\Components\Section::make('JSON-LD مخصص')
                                        ->schema([
                                            Forms\Components\Textarea::make('schema_data.raw_json')
                                                ->label('أدخل JSON-LD الكامل مباشرة')
                                                ->rows(8)
                                                ->placeholder(json_encode([
                                                    '@context' => 'https://schema.org',
                                                    '@type' => 'Organization',
                                                    'name' => 'اسم العيادة',
                                                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                                ->helperText('يُستخدم هذا الحقل فقط مع نوع "مخصص". أدخل JSON صالح يبدأ بـ @context'),
                                        ])
                                        ->visible(fn (Forms\Get $get) => $get('schema_type') === 'Custom')
                                        ->collapsible(),

                                ])->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('متقدم')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('robots')
                                        ->label('تعليمات محركات البحث (Robots)')
                                        ->options([
                                            'index_follow' => 'Index, Follow (الافتراضي)',
                                            'noindex_follow' => 'NoIndex, Follow',
                                            'index_nofollow' => 'Index, NoFollow',
                                            'noindex_nofollow' => 'NoIndex, NoFollow (إخفاء كامل)',
                                        ])->default('index_follow'),
                                    Forms\Components\TextInput::make('canonical_url')
                                        ->label('Canonical URL')
                                        ->url()
                                        ->placeholder('اتركه فارغاً للاستخدام الافتراضي'),
                                    Forms\Components\TextInput::make('page_key')
                                        ->label('مفتاح الصفحة')
                                        ->disabled()
                                        ->helperText('لا يمكن تعديل مفتاح الصفحة من هنا لضمان استقرار النظام'),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
