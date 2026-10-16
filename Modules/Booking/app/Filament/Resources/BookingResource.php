<?php

// app/Filament/Resources/BookingResource.php

namespace Modules\Booking\Filament\Resources;

use App\Helpers\SiteSettingHelper;
use App\Support\GuardsClinicModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Booking\Filament\Resources\BookingResource\Pages;
use Modules\Booking\Models\Booking;
use Modules\PatientCore\Models\Patient;

class BookingResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $modelLabel = 'حجز';

    protected static ?string $pluralModelLabel = 'الحجوزات';

    protected static ?string $navigationGroup = 'مركز العمليات والمواعيد';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'new')->count() > 10 ? 'danger' : 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات المريض')
                    ->schema([
                        Forms\Components\TextInput::make('patient_name')
                            ->label('اسم المريض')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('patient_phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('موعد الحجز')
                    ->schema([
                        Forms\Components\DatePicker::make('booking_date')
                            ->label('تاريخ الحجز')
                            ->required(),
                        Forms\Components\TimePicker::make('booking_time')
                            ->label('وقت الحجز')
                            ->required(),
                        Forms\Components\Select::make('service_id')
                            ->label('الخدمة المطلوبة')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('clinic_branch_id')
                            ->label('فرع العيادة')
                            ->relationship('clinicBranch', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(4),
                Forms\Components\Select::make('status')
                    ->label('حالة الحجز')
                    ->options([
                        'new' => 'طلب جديد',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'cancelled' => 'ملغى',
                        'completed' => 'مكتمل',
                    ])
                    ->required()
                    ->default('new'),
                Forms\Components\Textarea::make('patient_condition')
                    ->label('وصف الحالة')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات الإدارة')
                    ->columnSpanFull(),
                Forms\Components\Section::make('مصدر الحجز')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('source')->label('المصدر (Source)'),
                        Forms\Components\TextInput::make('medium')->label('الوسيط (Medium)'),
                        Forms\Components\TextInput::make('campaign')->label('الحملة (Campaign)'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('اسم المريض')
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label('تاريخ الحجز')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_time')
                    ->label('وقت الحجز'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('الخدمة')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clinicBranch.name')
                    ->label('الفرع')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('الحالة')
                    ->options([
                        'new' => 'طلب جديد',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'cancelled' => 'ملغى',
                        'completed' => 'مكتمل',
                    ]),
                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'طلب جديد',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'cancelled' => 'ملغى',
                        'completed' => 'مكتمل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل')->modalHeading('تفاصيل الحجز والبيانات'),
                Tables\Actions\Action::make('medical_profile')
                    ->label('الملف الطبي')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    ->action(function (Booking $record) {
                        $patient = Patient::where('phone', $record->patient_phone)->first();

                        if (! $patient) {
                            $patient = Patient::create([
                                'name' => $record->patient_name,
                                'phone' => $record->patient_phone,
                                'is_active' => true,
                            ]);
                        }

                        // Link bookings with same phone to the patient
                        Booking::where('patient_phone', $record->patient_phone)
                            ->whereNull('patient_id')
                            ->update(['patient_id' => $patient->id]);

                        if ($record->patient_id !== $patient->id) {
                            $record->patient_id = $patient->id;
                            $record->save();
                        }

                        return redirect(PatientResource::getUrl('profile', ['record' => $patient->id]));
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('whatsapp')
                    ->label('واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Booking $record): string => 'https://wa.me/'.SiteSettingHelper::formatWhatsappNumber($record->patient_phone).'?text='.urlencode("مرحباً {$record->patient_name}، نود تأكيد حجزك ليوم {$record->booking_date}"))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('تصدير كشف (CSV)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Arabic support
                            $csv .= "اسم المريض,رقم الهاتف,التاريخ,الوقت,الحالة\n";
                            foreach ($records as $record) {
                                $csv .= "\"{$record->patient_name}\",\"{$record->patient_phone}\",\"{$record->booking_date}\",\"{$record->booking_time}\",\"{$record->status}\"\n";
                            }

                            return response()->streamDownload(fn () => print ($csv), 'bookings-export-'.now()->format('Y-m-d').'.csv');
                        }),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'calendar' => Pages\BookingCalendar::route('/calendar'),
            'kanban' => Pages\BookingKanban::route('/kanban'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['service', 'clinicBranch']);
    }
}
