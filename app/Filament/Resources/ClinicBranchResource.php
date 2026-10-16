<?php

// app/Filament/Resources/ClinicBranchResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicBranchResource\Pages;
use App\Models\ClinicBranch;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClinicBranchResource extends Resource
{
    protected static ?string $model = ClinicBranch::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $modelLabel = 'فرع';

    protected static ?string $pluralModelLabel = 'الفروع';

    protected static ?string $navigationGroup = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات فرع العيادة')
                    ->description('إضافة تفاصيل الفرع ومواعيد العمل لتسهيل وصول المرضى إليك.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الفرع')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')->label('الرابط')->required()->maxLength(180)->unique(ignoreRecord: true)->regex('/^[\pL\pN]+(?:[-_][\pL\pN]+)*$/u')->default(fn () => 'branch-'.strtolower(Str::random(8))),
                        Forms\Components\Textarea::make('description')->label('نبذة عن الفرع'),
                        Forms\Components\Textarea::make('directions')->label('إرشادات الوصول'),
                        Forms\Components\Select::make('service_ids')->label('الخدمات المتاحة فعليًا')->multiple()->options(fn () => Service::pluck('name', 'id')),
                        Forms\Components\TextInput::make('meta_title')->label('عنوان البحث')->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')->label('وصف البحث')->maxLength(320),
                        Forms\Components\FileUpload::make('og_image')->label('صورة المشاركة')->image()->disk('public')->directory('landing'),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم هاتف الفرع')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('working_hours')
                            ->label('مواعيد العمل')
                            ->placeholder('السبت - الخميس: 5م - 10م')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان التفصيلي')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('google_maps_url')
                            ->label('رابط الموقع على خرائط جوجل')
                            ->url()
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('google_maps_embed')
                            ->label('كود تضمين الخريطة (Iframe)')
                            ->placeholder('كود iframe من خرائط جوجل')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حالة التفعيل (ظاهر في الموقع)')
                            ->required()
                            ->default(true),
                        Forms\Components\Hidden::make('order')
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('working_hours')
                    ->label('مواعيد العمل')
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
            'index' => Pages\ListClinicBranches::route('/'),
            'create' => Pages\CreateClinicBranch::route('/create'),
            'edit' => Pages\EditClinicBranch::route('/{record}/edit'),
        ];
    }
}
