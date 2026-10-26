<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteBookingRequestResource\Pages;
use App\Models\WebsiteBookingRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WebsiteBookingRequestResource extends Resource
{
    protected static ?string $model = WebsiteBookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $modelLabel = 'طلب موقع';

    protected static ?string $pluralModelLabel = 'طلبات الموقع';

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
                ...collect(['patient_name' => 'الاسم', 'patient_phone' => 'الهاتف', 'booking_date' => 'تاريخ الزيارة', 'service_name' => 'الخدمة', 'branch_name' => 'الفرع', 'source_host' => 'المصدر', 'created_at' => 'وقت الطلب'])->map(fn ($label, $field) => Forms\Components\TextInput::make($field)->label($label)->disabled()->dehydrated(false))->values()->all(),
            ])->columns(2),
            Forms\Components\Select::make('status')->label('الحالة')->options(WebsiteBookingRequest::STATUSES)->required()->in(array_keys(WebsiteBookingRequest::STATUSES)),
            Forms\Components\Textarea::make('admin_notes')->label('ملاحظات الإدارة')->maxLength(10000)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('patient_name')->label('الاسم')->searchable(),
            Tables\Columns\TextColumn::make('patient_phone')->label('الهاتف')->searchable(),
            Tables\Columns\TextColumn::make('booking_date')->label('تاريخ الزيارة')->date()->sortable(),
            Tables\Columns\TextColumn::make('service_name')->label('الخدمة'),
            Tables\Columns\TextColumn::make('branch_name')->label('الفرع'),
            Tables\Columns\TextColumn::make('status')->label('الحالة')->formatStateUsing(fn ($state) => WebsiteBookingRequest::STATUSES[$state] ?? $state)->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('وقت الطلب')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(WebsiteBookingRequest::STATUSES),
            Tables\Filters\SelectFilter::make('clinic_branch_id')->label('الفرع')->options(fn () => WebsiteBookingRequest::query()->whereNotNull('clinic_branch_id')->pluck('branch_name', 'clinic_branch_id')->all()),
            Tables\Filters\Filter::make('booking_date')->label('تاريخ الزيارة')->form([
                Forms\Components\DatePicker::make('from')->label('من'), Forms\Components\DatePicker::make('until')->label('إلى'),
            ])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('booking_date', '>=', $date))->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('booking_date', '<=', $date))),
        ])->actions([Tables\Actions\EditAction::make()->label('متابعة')])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListWebsiteBookingRequests::route('/'), 'edit' => Pages\EditWebsiteBookingRequest::route('/{record}/edit')];
    }
}
