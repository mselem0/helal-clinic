<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteBookingRequestResource\Pages;
use App\Models\WebsiteBookingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WebsiteBookingRequestResource extends Resource
{
    protected static ?string $model = WebsiteBookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $modelLabel = 'طلب موقع';

    protected static ?string $pluralModelLabel = 'الحجوزات';

    protected static ?string $navigationGroup = 'متابعة الطلبات';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'booking_manager', 'receptionist']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('تفاصيل الطلب')->schema([
                Forms\Components\TextInput::make('visit_type')->label('نوع الزيارة')->formatStateUsing(fn ($state) => WebsiteBookingRequest::VISIT_TYPES[$state] ?? 'غير محدد')->disabled()->dehydrated(false),
                ...collect(['patient_name' => 'الاسم', 'patient_phone' => 'الهاتف', 'booking_date' => 'تاريخ الزيارة', 'booking_time' => 'الساعة المختارة (القاهرة)', 'visit_window' => 'فترة الزيارة المعروضة', 'service_name' => 'الخدمة', 'branch_name' => 'الفرع', 'source_host' => 'المصدر', 'created_at' => 'وقت الطلب'])->map(fn ($label, $field) => Forms\Components\TextInput::make($field)->label($label)->disabled()->dehydrated(false))->values()->all(),
            ])->columns(2),
            Forms\Components\Section::make('ربط Tibbino')->schema([
                Forms\Components\TextInput::make('remote_request_id')->label('رقم الطلب في Tibbino')->disabled()->dehydrated(false),
                Forms\Components\Select::make('integration_state')->label('حالة الربط')->options(WebsiteBookingRequest::INTEGRATION_STATES)->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('sync_attempts')->label('محاولات الإرسال')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('last_sync_error')->label('نتيجة آخر محاولة')->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('next_sync_attempt_at')->label('المحاولة التالية')->disabled()->dehydrated(false),
            ])->columns(3)->visible(fn (?WebsiteBookingRequest $record) => filled($record?->integration_state)),
            Forms\Components\Textarea::make('admin_notes')->label('ملاحظات الإدارة')->maxLength(10000)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('patient_name')->label('الاسم')->searchable(),
            Tables\Columns\TextColumn::make('patient_phone')->label('الهاتف')->searchable(),
            Tables\Columns\TextColumn::make('booking_date')->label('تاريخ الزيارة')->date()->sortable(),
            Tables\Columns\TextColumn::make('booking_time')->label('الساعة المختارة')->placeholder('غير محددة'),
            Tables\Columns\TextColumn::make('visit_type')->label('نوع الزيارة')->formatStateUsing(fn ($state) => WebsiteBookingRequest::VISIT_TYPES[$state] ?? 'غير محدد'),
            Tables\Columns\TextColumn::make('visit_window')->label('فترة الزيارة المعروضة')->wrap(),
            Tables\Columns\TextColumn::make('service_name')->label('الخدمة'),
            Tables\Columns\TextColumn::make('branch_name')->label('الفرع'),
            Tables\Columns\SelectColumn::make('status')->label('الحالة')->options(WebsiteBookingRequest::STATUSES)->disabled(fn () => ! static::canViewAny())->beforeStateUpdated(fn () => abort_unless(static::canViewAny(), 403))->rules(['required', 'in:'.implode(',', array_keys(WebsiteBookingRequest::STATUSES))]),
            Tables\Columns\TextColumn::make('remote_request_id')->label('رقم طلب Tibbino')->placeholder('—')->searchable(),
            Tables\Columns\TextColumn::make('integration_state')->label('حالة الربط')->formatStateUsing(fn ($state) => WebsiteBookingRequest::INTEGRATION_STATES[$state] ?? $state)->placeholder('طلب موقع مستقل')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('وقت الطلب')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\Filter::make('booking_date')->label('تاريخ الزيارة')->columns(2)->columnSpanFull()->form([
                Forms\Components\DatePicker::make('from')->label('من'), Forms\Components\DatePicker::make('until')->label('إلى'),
            ])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('booking_date', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('booking_date', '<=', $date))),
        ], layout: FiltersLayout::AboveContent)->actions([Tables\Actions\EditAction::make()->label('التفاصيل والملاحظات')->modalHeading('تفاصيل طلب الحجز')->mutateFormDataUsing(fn (array $data): array => ['admin_notes' => $data['admin_notes'] ?? null])])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWebsiteBookingRequests::route('/')];
    }
}
