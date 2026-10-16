<?php

// app/Filament/Widgets/RecentBookingsTable.php

namespace Modules\Booking\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Booking\Models\Booking;

class RecentBookingsTable extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث طلبات الحجز';

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()->with(['service', 'clinicBranch'])->latest()->limit(7))
            ->columns([
                Tables\Columns\TextColumn::make('patient_name')
                    ->label('اسم المريض')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient_phone')
                    ->label('رقم الهاتف')
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الهاتف')
                    ->color('gray')
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('الخدمة المطلوبة')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('clinicBranch.name')
                    ->label('الفرع')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('booking_date')
                    ->date()
                    ->label('تاريخ الموعد')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'طلب جديد',
                        'confirmed' => 'مؤكد',
                        'in_progress' => 'قيد التنفيذ',
                        'cancelled' => 'ملغى',
                        'completed' => 'مكتمل',
                        default => $state
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'confirmed' => 'success',
                        'in_progress' => 'info',
                        'cancelled' => 'danger',
                        'completed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function (Booking $record) {
                        // Extract digit-only phone number
                        $phone = preg_replace('/[^0-9]/', '', $record->patient_phone);

                        // Prepare elegant clinical pre-filled message template
                        $serviceName = $record->service?->name ?? 'استشارة طبية';
                        $branchName = $record->clinicBranch?->name ?? 'العيادة';
                        $message = 'مرحباً بك يا أستاذ '.$record->patient_name.'، معك عيادة الدكتور محمد هلال استشاري أمراض النساء والتوليد والمناظير والحقن المجهري والصحة الجنسية. بخصوص طلب حجزك الموقر لـ ('.$serviceName.') بفرع ('.$branchName.')، نود تأكيد موعد كشفك وتفاصيل زيارتك.';

                        return 'https://wa.me/'.$phone.'?text='.urlencode($message);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view')
                    ->url(fn (Booking $record): string => route('filament.admin.resources.bookings.edit', $record))
                    ->icon('heroicon-o-arrow-left-start-on-rectangle')
                    ->label('عرض الملف')
                    ->color('gray'),
            ]);
    }
}
