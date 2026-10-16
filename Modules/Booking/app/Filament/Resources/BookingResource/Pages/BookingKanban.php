<?php

// app/Filament/Resources/BookingResource/Pages/BookingKanban.php

namespace Modules\Booking\Filament\Resources\BookingResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Modules\Booking\Filament\Resources\BookingResource;
use Modules\Booking\Models\Booking;

class BookingKanban extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.resources.booking-resource.pages.booking-kanban';

    protected static ?string $title = 'لوحة تدفق المرضى (Kanban)';

    public $statuses = [
        'new' => 'طلب جديد',
        'confirmed' => 'موعد مؤكد',
        'in_progress' => 'قيد التنفيذ',
        'cancelled' => 'ملغى',
        'completed' => 'تم الكشف',
    ];

    public function getBookings($status)
    {
        return Booking::where('status', $status)->orderBy('booking_date')->get();
    }

    public function updateStatus($id, $status)
    {
        $booking = Booking::find($id);
        $booking->status = $status;
        $booking->save();

        Notification::make()->title('تم تحديث حالة المريض')->success()->send();
    }
}
