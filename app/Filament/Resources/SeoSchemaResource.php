<?php

// app/Filament/Resources/SeoSchemaResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoSchemaResource\Pages;
use App\Models\SeoSchema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeoSchemaResource extends Resource
{
    protected static ?string $model = SeoSchema::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $modelLabel = 'Schema Markup';

    protected static ?string $pluralModelLabel = 'إدارة Schema';

    protected static ?string $navigationGroup = 'SEO & التسويق';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الـ Schema')
                    ->schema([
                        Forms\Components\Select::make('seo_page_id')
                            ->label('الصفحة المرتبطة')
                            ->relationship('page', 'page_label')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('schema_type')
                            ->label('نوع Schema')
                            ->options([
                                'Physician' => 'طبيب (Physician)',
                                'FAQPage' => 'الأسئلة الشائعة (FAQPage)',
                                'VideoObject' => 'فيديو (VideoObject)',
                                'Custom' => 'مخصص (Manual JSON)',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعّل')
                            ->default(true),
                        Forms\Components\Toggle::make('is_primary')
                            ->label('Schema أساسي للصفحة')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('محرر البيانات الموجه (Guided Editor)')
                    ->visible(fn ($get) => in_array($get('schema_type'), ['Physician', 'FAQPage']))
                    ->schema([
                        Forms\Components\Grid::make()
                            ->visible(fn ($get) => $get('schema_type') === 'Physician')
                            ->schema([
                                Forms\Components\TextInput::make('schema_data.name')->label('الاسم الكامل'),
                                Forms\Components\TextInput::make('schema_data.jobTitle')->label('المسمى الوظيفي'),
                                Forms\Components\TagsInput::make('schema_data.knowsAbout')->label('التخصصات'),
                            ]),
                        Forms\Components\Repeater::make('schema_data.questions')
                            ->label('الأسئلة والأجوبة')
                            ->visible(fn ($get) => $get('schema_type') === 'FAQPage')
                            ->schema([
                                Forms\Components\TextInput::make('question')->label('السؤال')->required(),
                                Forms\Components\Textarea::make('answer')->label('الإجابة')->required(),
                            ])->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('البيانات الخام (JSON)')
                    ->visible(fn ($get) => $get('schema_type') === 'Custom' || empty($get('schema_type')))
                    ->schema([
                        Forms\Components\KeyValue::make('schema_data')
                            ->label('بيانات Schema')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('page.page_label')
                    ->label('الصفحة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('schema_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('أساسي')
                    ->boolean(),
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
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('اختبار')
                    ->icon('heroicon-o-beaker')
                    ->url(fn (SeoSchema $record) => 'https://search.google.com/test/rich-results')
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListSeoSchemas::route('/'),
            'create' => Pages\CreateSeoSchema::route('/create'),
            'edit' => Pages\EditSeoSchema::route('/{record}/edit'),
        ];
    }
}
