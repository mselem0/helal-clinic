<?php

// app/Filament/Resources/SeoRedirectResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\SeoRedirectResource\Pages;
use App\Models\SeoRedirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SeoRedirectResource extends Resource
{
    protected static ?string $model = SeoRedirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $modelLabel = 'تحويل';

    protected static ?string $pluralModelLabel = 'التحويلات (Redirects)';

    protected static ?string $navigationGroup = 'SEO & التسويق';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل التحويل')
                    ->schema([
                        Forms\Components\TextInput::make('source_url')
                            ->label('الرابط القديم (المصدر)')
                            ->placeholder('/old-page')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('target_url')
                            ->label('الرابط الجديد (الهدف)')
                            ->placeholder('/new-page')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('redirect_type')
                            ->label('نوع التحويل')
                            ->options([
                                '301' => '301 — دائم',
                                '302' => '302 — مؤقت',
                                '307' => '307 — مؤقت (إعادة توجيه الطلب)',
                                '410' => '410 — محذوف نهائياً',
                            ])
                            ->default('301')
                            ->required(),
                        Forms\Components\Select::make('match_type')
                            ->label('نوع المطابقة')->helperText('المطابقة التامة للمسار هي المدعومة حاليًا.')
                            ->options([
                                'exact' => 'مطابقة تامة',
                            ])
                            ->default('exact')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعل')
                            ->default(true),
                        Forms\Components\TextInput::make('notes')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_url')
                    ->label('المصدر')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_url')
                    ->label('الهدف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('redirect_type')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('hit_count')
                    ->label('الزيارات')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->label('آخر استخدام')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('redirect_type')
                    ->options([
                        '301' => '301',
                        '302' => '302',
                        '410' => '410',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListSeoRedirects::route('/'),
            'create' => Pages\CreateSeoRedirect::route('/create'),
            'edit' => Pages\EditSeoRedirect::route('/{record}/edit'),
        ];
    }
}
