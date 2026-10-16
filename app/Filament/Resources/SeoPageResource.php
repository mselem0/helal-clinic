<?php

// app/Filament/Resources/SeoPageResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoPageResource\Pages;
use App\Models\SeoPage;
use App\Services\SeoScoreCalculator;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SeoPageResource extends Resource
{
    protected static ?string $model = SeoPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'SEO لصفحة';

    protected static ?string $pluralModelLabel = 'SEO الصفحات';

    protected static ?string $navigationGroup = 'SEO & التسويق';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SeoTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('عام')
                            ->schema([
                                Forms\Components\Placeholder::make('guide_general')
                                    ->label('💡 دليل تحسين الأساسيات')
                                    ->content(new HtmlString('
                                        <div class="text-xs space-y-2">
                                            <p><strong>الكلمات المفتاحية:</strong> هي الكلمات التي تريد أن تظهر فيها عند بحث المرضى (مثلاً: "علاج تأخر الإنجاب").</p>
                                            <p><strong>عنوان الميتا:</strong> هو العنوان الذي يظهر في جوجل. اجعله جذاباً ويحتوي على الكلمة الأهم (مثال: د. محمد هلال | أفضل طبيب لعلاج تأخر الإنجاب).</p>
                                            <p><strong>وصف الميتا:</strong> شرح بسيط يظهر تحت العنوان في جوجل. اكتب جملتين تشجع المريض على الدخول لموقعك.</p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                Forms\Components\TagsInput::make('focus_keywords')
                                    ->label('الكلمات المفتاحية الأساسية')
                                    ->placeholder('أضف كلمة واضغط Enter')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('meta_title')
                                    ->label('عنوان الميتا (Meta Title)')
                                    ->maxLength(60)
                                    ->live()
                                    ->helperText(fn ($state) => strlen($state ?? '').'/60')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('meta_description')
                                    ->label('وصف الميتا (Meta Description)')
                                    ->maxLength(160)
                                    ->live()
                                    ->helperText(fn ($state) => strlen($state ?? '').'/160')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\TagsInput::make('meta_keywords')
                                    ->label('كلمات الميتا المفتاحية')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('canonical_url')
                                    ->label('Canonical URL')
                                    ->url()
                                    ->placeholder('اتركه فارغاً للاستخدام الافتراضي'),
                            ]),
                        Forms\Components\Tabs\Tab::make('مظهر البحث')
                            ->schema([
                                Forms\Components\Placeholder::make('guide_appearance')
                                    ->label('💡 دليل مظهر وفهرسة الصفحة')
                                    ->content(new HtmlString('
                                        <div class="text-xs space-y-2">
                                            <p><strong>تعليمات الـ Robots:</strong> تحدد ما إذا كنت تريد لجوجل أن يظهر هذه الصفحة في النتائج أم لا.</p>
                                            <p>⚠️ لا تختر <strong>NoIndex</strong> إلا إذا كنت تريد إخفاء الصفحة تماماً من محركات البحث.</p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('robots')
                                    ->label('تعليمات محركات البحث (Robots)')
                                    ->options([
                                        'index_follow' => 'Index, Follow (الافتراضي)',
                                        'noindex_follow' => 'NoIndex, Follow',
                                        'index_nofollow' => 'Index, NoFollow',
                                        'noindex_nofollow' => 'NoIndex, NoFollow (إخفاء كامل)',
                                    ])->default('index_follow'),
                                Forms\Components\TextInput::make('page_key')
                                    ->label('مفتاح الصفحة (Slug/Key)')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('page_label')
                                    ->label('اسم الصفحة الداخلي')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('السوشيال ميديا')
                            ->schema([
                                Forms\Components\Placeholder::make('guide_social')
                                    ->label('💡 دليل المشاركة الاجتماعية')
                                    ->content(new HtmlString('
                                        <div class="text-xs space-y-2">
                                            <p>تحكم في كيفية ظهور رابط هذه الصفحة عند إرسالها عبر <strong>واتساب</strong> أو فيسبوك.</p>
                                            <p><strong>صورة الـ OG:</strong> هي الصورة التي تظهر مع الرابط. الحجم المثالي هو 1200×630 بكسل.</p>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Open Graph (Facebook/WhatsApp)')
                                    ->schema([
                                        Forms\Components\TextInput::make('og_title')->label('عنوان OG'),
                                        Forms\Components\Textarea::make('og_description')->label('وصف OG')->rows(2),
                                        SpatieMediaLibraryFileUpload::make('seo-page-og')->collection('seo-page-og')
                                            ->label('صورة OG'),
                                        Forms\Components\Select::make('og_type')
                                            ->label('نوع المحتوى')
                                            ->options(['website' => 'Website', 'article' => 'Article', 'profile' => 'Profile']),
                                    ])->columns(2),
                                Forms\Components\Section::make('Twitter Card')
                                    ->schema([
                                        Forms\Components\TextInput::make('twitter_title')->label('عنوان تويتر'),
                                        Forms\Components\Textarea::make('twitter_description')->label('وصف تويتر')->rows(2),
                                        SpatieMediaLibraryFileUpload::make('seo-page-twitter')->collection('seo-page-twitter')
                                            ->label('صورة تويتر'),
                                        Forms\Components\Select::make('twitter_card')
                                            ->label('نوع بطاقة تويتر')
                                            ->options(['summary' => 'Small Card', 'summary_large_image' => 'Large Image Card']),
                                    ])->columns(2),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->color(fn ($state) => $state > 80 ? 'success' : ($state > 50 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoPages::route('/'),
            'create' => Pages\CreateSeoPage::route('/create'),
            'edit' => Pages\EditSeoPage::route('/{record}/edit'),
        ];
    }

    public static function afterSave(SeoPage $record): void
    {
        $calculator = new SeoScoreCalculator;
        $result = $calculator->calculate($record);
        $record->update([
            'seo_score' => $result['score'],
            'score_breakdown' => $result['breakdown'],
        ]);
    }
}
