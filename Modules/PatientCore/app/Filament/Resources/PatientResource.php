<?php

namespace Modules\PatientCore\Filament\Resources;

use App\Helpers\SiteSettingHelper;
use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\PatientCore\Filament\Resources\PatientResource\Pages;
use Modules\PatientCore\Models\Patient;

class PatientResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'مريض';

    protected static ?string $pluralModelLabel = 'المرضى';

    protected static ?string $navigationGroup = 'مركز العمليات والمواعيد';

    public static function getNavigationBadge(): ?string
    {
        // Count of patients created in the last 7 days
        $count = static::getModel()::where('created_at', '>=', now()->subDays(7))->count();

        return $count ? "+{$count}" : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('البيانات الأساسية للمريض')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم المريض الكامل')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('تاريخ الميلاد')
                            ->maxDate(now()),
                        Forms\Components\Select::make('gender')
                            ->label('الجنس')
                            ->options([
                                'male' => 'ذكر',
                                'female' => 'أنثى',
                            ]),
                        Forms\Components\TextInput::make('national_id')
                            ->label('رقم الهوية / الرقم القومي')
                            ->maxLength(255),
                        Forms\Components\Select::make('blood_type')
                            ->label('فصيلة الدم')
                            ->options([
                                'A+' => 'A+',
                                'A-' => 'A-',
                                'B+' => 'B+',
                                'B-' => 'B-',
                                'AB+' => 'AB+',
                                'AB-' => 'AB-',
                                'O+' => 'O+',
                                'O-' => 'O-',
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('العنوان')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط / مستمر في العيادة')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('بيانات الطوارئ والملاحظات')
                    ->schema([
                        Forms\Components\TextInput::make('emergency_contact_name')
                            ->label('اسم جهة الاتصال في الطوارئ')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('emergency_contact_phone')
                            ->label('رقم هاتف الطوارئ')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات عامة حول المريض')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المريض')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('العمر')
                    ->state(function (Patient $record): ?string {
                        return $record->age ? "{$record->age} سنة" : 'غير محدد';
                    }),
                Tables\Columns\TextColumn::make('blood_type')
                    ->label('فصيلة الدم')
                    ->badge()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('visit_records_count')
                    ->label('عدد الزيارات')
                    ->counts('visitRecords')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('last_visit')
                    ->label('آخر زيارة')
                    ->state(function (Patient $record): ?string {
                        $lastVisit = $record->last_visit;
                        if (! $lastVisit) {
                            return 'لا توجد زيارات بعد';
                        }

                        return $lastVisit->visit_date->format('Y-m-d').' ('.$lastVisit->visit_date->diffForHumans().')';
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('حالة النشاط')
                    ->options([
                        '1' => 'نشط',
                        '0' => 'غير نشط',
                    ]),
                Tables\Filters\SelectFilter::make('blood_type')
                    ->label('فصيلة الدم')
                    ->options([
                        'A+' => 'A+',
                        'A-' => 'A-',
                        'B+' => 'B+',
                        'B-' => 'B-',
                        'AB+' => 'AB+',
                        'AB-' => 'AB-',
                        'O+' => 'O+',
                        'O-' => 'O-',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('profile')
                    ->label('الملف الطبي')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    ->url(fn (Patient $record): string => static::getUrl('profile', ['record' => $record->id])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('whatsapp')
                    ->label('واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Patient $record): string => 'https://wa.me/'.SiteSettingHelper::formatWhatsappNumber($record->phone))
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
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
            'profile' => Pages\ViewPatientProfile::route('/{record}/profile'),
        ];
    }
}
