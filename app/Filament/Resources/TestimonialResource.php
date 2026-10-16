<?php

// app/Filament/Resources/TestimonialResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'رأي مريض';

    protected static ?string $pluralModelLabel = 'آراء المرضى';

    protected static ?string $navigationGroup = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source_url')->label('رابط مصدر التقييم')->maxLength(1000)->url(),

                Forms\Components\Section::make('بيانات رأي المريض')
                    ->description('إضافة تجربة أو رأي لمريض لعرضها في الموقع لبناء الثقة.')
                    ->schema([
                        Forms\Components\TextInput::make('patient_name')
                            ->label('اسم المريض')
                            ->required()
                            ->placeholder('أحمد م.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('patient_initial')
                            ->label('الحرف الأول (للرمز)')
                            ->placeholder('أ')
                            ->maxLength(1),
                        Forms\Components\Select::make('avatar_color')
                            ->label('لون خلفية الرمز')
                            ->options([
                                'cyan' => 'سماوي',
                                'navy' => 'كحلي',
                                'green' => 'أخضر',
                                'purple' => 'بنفسجي',
                            ])
                            ->default('cyan'),
                        Forms\Components\Select::make('rating')
                            ->label('التقييم')
                            ->options([
                                1 => '1 نجمة',
                                2 => '2 نجمتان',
                                3 => '3 نجوم',
                                4 => '4 نجوم',
                                5 => '5 نجوم',
                            ])
                            ->default(5)
                            ->required(),
                        Forms\Components\Textarea::make('review_text')
                            ->label('نص الرأي')
                            ->required()
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
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('اسم المريض')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('التقييم')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
