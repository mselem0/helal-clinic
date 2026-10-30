<?php

// app/Filament/Resources/ClinicBranchResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicBranchResource\Pages;
use App\Models\ClinicBranch;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم هاتف الفرع')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('working_hours')
                            ->label('وصف مواعيد العمل العام')
                            ->helperText('نص تعريفي للفروع التي لم يُفعّل جدول الحجز المنظم لها.')
                            ->placeholder('السبت - الخميس: 5م - 10م')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('العنوان التفصيلي')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('حالة التفعيل (ظاهر في الموقع)')
                            ->required()
                            ->default(true),
                        Forms\Components\Hidden::make('order')->default(0),
                    ])->columns(2),
                Forms\Components\Section::make('إعدادات إضافية للفرع')
                    ->description('الخدمات المرتبطة، موقع الفرع، الحجز، وبيانات المشاركة والبحث.')
                    ->schema([
                        Forms\Components\Select::make('service_ids')->label('الخدمات المتاحة فعليًا')->multiple()->options(fn () => Service::pluck('name', 'id')),
                        Forms\Components\TextInput::make('meta_title')->label('عنوان البحث')->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')->label('وصف البحث')->maxLength(320),
                        Forms\Components\FileUpload::make('og_image')->label('صورة المشاركة')->image()->disk('public')->directory('landing'),
                        Forms\Components\Toggle::make('booking_schedule_enabled')
                            ->label('تفعيل مواعيد الحجز لهذا الفرع')
                            ->live()
                            ->default(false)
                            ->helperText('عند عدم التفعيل يستمر الحجز بالطريقة الحالية.'),
                        Forms\Components\Toggle::make('booking_time_enabled')
                            ->label('تفعيل اختيار ساعة الزيارة')
                            ->default(true)
                            ->helperText('تختار المريضة موعدًا من قائمة كل نصف ساعة داخل مواعيد اليوم المسجلة أدناه. لا يوجد حد لعدد الحجوزات في نفس الموعد.')
                            ->visible(fn (Get $get): bool => (bool) $get('booking_schedule_enabled')),
                        Forms\Components\Section::make('الجدول الأسبوعي للحجز')
                            ->description('المواعيد بتوقيت القاهرة وتُطبّق على جميع خدمات الفرع. اترك اليوم مغلقًا عند عدم استقبال الحجوزات.')
                            ->schema(self::weeklyScheduleFields())
                            ->columns(1)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('booking_schedule_enabled')),
                        Forms\Components\Repeater::make('schedule_exceptions')
                            ->label('استثناءات التواريخ')
                            ->helperText('يمكن إغلاق يوم محدد أو تحديد ساعات مختلفة لذلك اليوم فقط.')
                            ->default([])
                            ->schema([
                                Forms\Components\DatePicker::make('date')->label('التاريخ')->required()->distinct(),
                                Forms\Components\Toggle::make('closed')->label('مغلق طوال اليوم')->default(false)->live(),
                                Forms\Components\TimePicker::make('from')->label('من')->seconds(false)->required(fn (Get $get): bool => ! (bool) $get('closed'))->hidden(fn (Get $get): bool => (bool) $get('closed'))->rules(['date_format:H:i']),
                                Forms\Components\TimePicker::make('to')->label('إلى')->seconds(false)->required(fn (Get $get): bool => ! (bool) $get('closed'))->hidden(fn (Get $get): bool => (bool) $get('closed'))->rules([
                                    'date_format:H:i',
                                    function (Get $get): \Closure {
                                        return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                            $from = $get('from');
                                            if (is_string($from) && is_string($value) && $from >= $value && ! ($value === '00:00' && $from > '00:00')) {
                                                $fail('يجب أن يكون وقت الإغلاق بعد وقت الفتح.');
                                            }
                                        };
                                    },
                                ]),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->collapsible()
                            ->visible(fn (Get $get): bool => (bool) $get('booking_schedule_enabled'))
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
                    ])->columns(2)->collapsible()->collapsed(),
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

    /** @return array<int, Forms\Components\Component> */
    private static function weeklyScheduleFields(): array
    {
        $days = [
            0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
            4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
        ];

        $fields = [];
        foreach ($days as $weekday => $label) {
            $fields[] = Forms\Components\Fieldset::make($label)
                ->schema([
                    Forms\Components\Toggle::make("weekly_schedule.{$weekday}.open")
                        ->label('مفتوح للحجز')
                        ->default(false)
                        ->live(),
                    Forms\Components\TimePicker::make("weekly_schedule.{$weekday}.from")
                        ->label('من')->seconds(false)
                        ->required(fn (Get $get): bool => (bool) $get("weekly_schedule.{$weekday}.open"))
                        ->hidden(fn (Get $get): bool => ! (bool) $get("weekly_schedule.{$weekday}.open"))
                        ->rules(['date_format:H:i']),
                    Forms\Components\TimePicker::make("weekly_schedule.{$weekday}.to")
                        ->label('إلى')->seconds(false)
                        ->required(fn (Get $get): bool => (bool) $get("weekly_schedule.{$weekday}.open"))
                        ->hidden(fn (Get $get): bool => ! (bool) $get("weekly_schedule.{$weekday}.open"))
                        ->rules([
                            'date_format:H:i',
                            function (Get $get) use ($weekday): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get, $weekday): void {
                                    $from = $get("weekly_schedule.{$weekday}.from");
                                    if (is_string($from) && is_string($value) && $from >= $value && ! ($value === '00:00' && $from > '00:00')) {
                                        $fail('يجب أن يكون وقت الإغلاق بعد وقت الفتح.');
                                    }
                                };
                            },
                        ]),
                ])->columns(3);
        }

        return $fields;
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
