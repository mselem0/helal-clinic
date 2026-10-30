<?php

namespace App\Filament\Resources\WebsiteBookingRequestResource\Pages\Concerns;

use App\Models\WebsiteBookingRequest;
use App\Models\WebsiteBookingSheetChange;
use App\Models\WebsiteBookingSheetSync;
use App\Services\GoogleSheets\BookingSheetsSync;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

trait ConnectsGoogleSheets
{
    public function canManageSheets(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function sheetConflicts(): Collection
    {
        abort_unless($this->canManageSheets(), 403);

        return WebsiteBookingSheetSync::whereNotNull('conflicts')->limit(50)->get();
    }

    public function resolveSheetConflict(int $id, string $field, string $choice): void
    {
        abort_unless($this->canManageSheets(), 403);
        abort_unless(in_array($field, ['patient_name', 'patient_phone', 'booking_date', 'service_name', 'branch_name', 'status', 'admin_notes'], true) && in_array($choice, ['local', 'sheet'], true), 422);
        try {
            $resolved = app(BookingSheetsSync::class)->resolveConflict(WebsiteBookingRequest::findOrFail($id), $field, $choice);
            if ($resolved) {
                Notification::make()->title('تم اعتماد التعديل')->body('سيتم استكمال المزامنة تلقائيًا.')->success()->send();
            } else {
                Notification::make()->title('تغيرت البيانات منذ عرض التعارض')->body('راجع القيم الحالية قبل اعتماد التعديل.')->warning()->send();
            }
        } catch (\Throwable) {
            Notification::make()->title('تعذر اعتماد التعديل')->body('تأكد من صحة القيمة والاتصال بالشيت، ثم حاول مرة أخرى.')->danger()->send();
        }
    }

    protected function googleSheetsActions(): array
    {
        return [
            Action::make('googleSheets')->label('ربط Google Sheets')->icon('heroicon-o-table-cells')->color('gray')
                ->visible(fn () => $this->canManageSheets())
                ->modalHeading('ربط الحجوزات بـ Google Sheets')
                ->modalDescription('شاركي الشيت مع بريد الربط كمحرر، ثم ضعي رابط الشيت. سيُنشأ تبويب مستقل لهذا الموقع، وتُزامن بيانات الحجز في الاتجاهين مع الاحتفاظ بالتعديلات المتعارضة للمراجعة.')
                ->form([
                    Placeholder::make('service_email')->label('بريد الربط')->content(fn () => app(BookingSheetsSync::class)->serviceAccountEmail() ?: 'حساب الربط لم يُجهز بعد. يرجى التواصل مع مسؤول الموقع.'),
                    TextInput::make('spreadsheet_url')->label('رابط Google Sheets')->required()->maxLength(500)->placeholder('https://docs.google.com/spreadsheets/d/...'),
                ])
                ->modalSubmitActionLabel('اختبار وحفظ')
                ->action(function (array $data): void {
                    abort_unless($this->canManageSheets(), 403);
                    try {
                        app(BookingSheetsSync::class)->connect($data['spreadsheet_url']);
                        Notification::make()->title('تم ربط الشيت، وستُزامن الطلبات الحالية تلقائيًا')->success()->send();
                    } catch (\Throwable) {
                        Notification::make()->title('تعذر ربط الشيت')->body('تأكدي من مشاركة الشيت مع بريد الربط كمحرر، ومن صحة الرابط وإعداد حساب الخدمة.')->danger()->send();
                    }
                }),
            Action::make('sheetHistory')->label('سجل التغييرات')->icon('heroicon-o-clock')->color('gray')
                ->visible(fn () => $this->canManageSheets())
                ->modalHeading('سجل تغييرات المزامنة')->modalWidth('7xl')
                ->modalContent(fn () => view('filament.resources.website-booking-requests.sheets-history', [
                    'changes' => WebsiteBookingSheetChange::query()->latest('id')->limit(100)->get(),
                ]))->modalSubmitAction(false)->modalCancelActionLabel('إغلاق'),
            Action::make('disconnectSheets')->label('إلغاء الربط')->icon('heroicon-o-link-slash')->color('danger')
                ->visible(fn () => $this->canManageSheets() && app(BookingSheetsSync::class)->connection()->connected_at !== null)
                ->requiresConfirmation()
                ->modalHeading('إلغاء ربط Google Sheets؟')
                ->modalDescription('ستتوقف المزامنة مع الشيت الحالي. ستظل الحجوزات محفوظة على الموقع، ولن نحذف بيانات الشيت القديم. يمكنك بعدها وضع رابط شيت جديد.')
                ->modalSubmitActionLabel('إلغاء الربط')
                ->action(function (): void {
                    abort_unless($this->canManageSheets(), 403);
                    try {
                        app(BookingSheetsSync::class)->disconnect();
                        Notification::make()->title('تم إلغاء الربط')->body('يمكنك الآن ربط شيت جديد. جميع الحجوزات محفوظة.')->success()->send();
                    } catch (\Throwable) {
                        Notification::make()->title('تعذر إلغاء الربط الآن')->body('قد تكون المزامنة جارية. حاول مرة أخرى بعد قليل.')->warning()->send();
                    }
                }),
            Action::make('syncSheets')->label('مزامنة الآن')->icon('heroicon-o-arrow-path')->color('gray')
                ->visible(fn () => $this->canManageSheets() && app(BookingSheetsSync::class)->connection()->connected_at !== null)
                ->action(function (): void {
                    abort_unless($this->canManageSheets(), 403);
                    $result = app(BookingSheetsSync::class)->syncNow();
                    if ($result['conflicted'] ?? 0) {
                        Notification::make()->title('توجد تعديلات تحتاج مراجعة')->body('تم الاحتفاظ بتعديلات الشيت واللوحة؛ اختر القيمة المطلوبة من التعارضات أدناه.')->warning()->send();
                    } elseif ($result['deferred'] ?? false) {
                        Notification::make()->title('المزامنة مؤجلة مؤقتًا')->body('ستعاد المحاولة تلقائيًا بعد انتهاء حد Google.')->warning()->send();
                    } elseif ($result['failed'] ?? 0) {
                        Notification::make()->title('بعض الطلبات تنتظر إعادة المزامنة')->body('حجوزاتك محفوظة وسيُعاد إرسالها تلقائيًا.')->warning()->send();
                    } else {
                        Notification::make()->title(($result['busy'] ?? false) ? 'المزامنة جارية بالفعل' : 'اكتملت المزامنة')->success()->send();
                    }
                }),
        ];
    }
}
