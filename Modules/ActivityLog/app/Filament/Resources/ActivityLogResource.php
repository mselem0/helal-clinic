<?php

namespace Modules\ActivityLog\Filament\Resources;

use App\Models\User;
use App\Support\GuardsClinicModule;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\ActivityLog\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    use GuardsClinicModule;

    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?string $label = 'سجل العمليات';

    protected static ?string $pluralLabel = 'سجلات العمليات';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    IconColumn::make('description')
                        ->label('')
                        ->icon(fn (string $state): string => match ($state) {
                            'created' => 'heroicon-m-plus-circle',
                            'updated' => 'heroicon-m-pencil-square',
                            'deleted' => 'heroicon-m-trash',
                            default => 'heroicon-m-information-circle',
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default => 'gray',
                        })
                        ->grow(false),

                    Stack::make([
                        TextColumn::make('causer.name')
                            ->label('المستخدم')
                            ->weight('bold')
                            ->default('النظام'),
                        TextColumn::make('description_arabic')
                            ->state(function (Activity $record): string {
                                $action = match ($record->description) {
                                    'created' => 'قام بإضافة',
                                    'updated' => 'قام بتعديل',
                                    'deleted' => 'قام بحذف',
                                    'login' => 'قام بتسجيل الدخول',
                                    'logout' => 'قام بتسجيل الخروج',
                                    default => $record->description,
                                };

                                $subject = self::getSubjectLabel($record);

                                return "{$action} {$subject}";
                            })
                            ->size('sm'),
                    ]),

                    Stack::make([
                        TextColumn::make('created_at')
                            ->dateTime('H:i')
                            ->color('gray')
                            ->size('xs')
                            ->alignment('end'),
                        TextColumn::make('created_at')
                            ->since()
                            ->color('primary')
                            ->size('xs')
                            ->alignment('end'),
                    ])->grow(false),
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('المستخدم')
                    ->options(User::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query
                            ->where('causer_id', $data['value'])
                            ->where('causer_type', (new User)->getMorphClass());
                    }),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->options([
                        'Modules\Booking\Models\Booking' => 'الحجوزات',
                        'App\Models\Service' => 'الخدمات',
                        'App\Models\User' => 'المستخدمين',
                        'Modules\Whatsapp\Models\WhatsappTemplate' => 'قوالب واتساب',
                        'App\Models\SeoPage' => 'SEO الصفحات',
                        'App\Models\Testimonial' => 'آراء المرضى',
                        'App\Models\Video' => 'المحتوى المرئي',
                        'App\Models\PageSection' => 'أقسام الصفحات',
                    ])
                    ->label('القسم'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('من '.Carbon::parse($data['created_from'])->format('d/m/Y'))
                                ->removeField('created_from');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('إلى '.Carbon::parse($data['created_until'])->format('d/m/Y'))
                                ->removeField('created_until');
                        }

                        return $indicators;
                    }),
            ])
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('تفاصيل العملية')
                    ->modalWidth('2xl'),
            ])
            ->bulkActions([
                // Disable bulk actions for safety
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('معلومات العملية')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label('بواسطة')
                                    ->default('النظام'),
                                TextEntry::make('created_at')
                                    ->label('التوقيت')
                                    ->dateTime('Y/m/d H:i:s'),
                                TextEntry::make('description')
                                    ->label('نوع العملية')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'created' => 'إضافة',
                                        'updated' => 'تعديل',
                                        'deleted' => 'حذف',
                                        default => $state,
                                    }),
                            ]),
                    ]),

                InfoSection::make('التغييرات')
                    ->visible(fn (Activity $record) => $record->description === 'updated' && ! empty($record->properties['attributes']))
                    ->schema([
                        ViewEntry::make('properties')
                            ->label('')
                            ->view('filament.resources.activity-log.components.diff-view'),
                    ]),

                InfoSection::make('البيانات المُضافة')
                    ->visible(fn (Activity $record) => $record->description === 'created')
                    ->schema([
                        ViewEntry::make('properties.attributes')
                            ->label('')
                            ->view('filament.resources.activity-log.components.attributes-view'),
                    ]),
            ]);
    }

    protected static function getSubjectLabel(Activity $record): string
    {
        $subjectType = $record->subject_type;
        $label = match ($subjectType) {
            'Modules\Booking\Models\Booking' => 'حجز مريض',
            'App\Models\Service' => 'خدمة طبية',
            'App\Models\User' => 'مستخدم للنظام',
            'Modules\Whatsapp\Models\WhatsappTemplate' => 'قالب واتساب',
            'App\Models\SeoPage' => 'إعدادات SEO',
            'App\Models\PageSection' => 'قسم بالصفحة',
            'Modules\Whatsapp\Models\WhatsappLog' => 'سجل واتساب',
            'App\Models\Testimonial' => 'رأي مريض',
            'App\Models\Video' => 'فيديو',
            'App\Models\ClinicBranch' => 'فرع عيادة',
            'App\Models\HeroSetting' => 'إعدادات الواجهة',
            'App\Models\SiteSetting' => 'إعدادات عامة',
            default => $subjectType ? class_basename($subjectType) : 'غير معروف',
        };

        $subjectName = null;
        if ($record->subject) {
            $subjectName = $record->subject->name
                ?? $record->subject->patient_name
                ?? $record->subject->title
                ?? $record->subject->label
                ?? null;
        }

        return $subjectName ? "{$label} ({$subjectName})" : $label;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
