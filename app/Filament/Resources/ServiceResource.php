<?php

// app/Filament/Resources/ServiceResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $modelLabel = 'خدمة طبية';

    protected static ?string $pluralModelLabel = 'الخدمات الطبية';

    protected static ?string $navigationGroup = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')->label('صورة البطاقة')->disk('public')->directory('landing')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(51200),
                Forms\Components\TextInput::make('card_title')->label('عنوان البطاقة')->maxLength(1000),

                Forms\Components\Section::make('تفاصيل الخدمة الطبية')
                    ->description('أدخل تفاصيل الخدمة التي تقدمها العيادة لعرضها في الموقع.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الخدمة')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')->regex('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u')
                            ->label('الرابط (Slug)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('وصف الخدمة القصير')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('rich_description')
                            ->label('الوصف الكامل للخدمة')
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('service-image')->collection('service-image')
                            ->label('صورة الخدمة'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حالة التفعيل (ظاهر في الموقع)')
                            ->required()
                            ->default(true),
                        Forms\Components\Hidden::make('order')
                            ->default(0),
                    ])->columns(2),
                Forms\Components\Section::make('SEO')->hidden()
                    ->schema([
                        Forms\Components\FileUpload::make('og_image')->label('صورة المشاركة')->image()->disk('public')->directory('landing'),
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->maxLength(255),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('service-image')
                    ->label('الصورة'),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل؟')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('order')
            ->defaultSort('order')
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
