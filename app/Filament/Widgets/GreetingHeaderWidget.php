<?php

// app/Filament/Widgets/GreetingHeaderWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Whatsapp\Models\WhatsappLog;

class GreetingHeaderWidget extends Widget
{
    protected static string $view = 'filament.widgets.greeting-header-widget';

    protected static ?int $sort = -10; // Top of the page

    protected int|string|array $columnSpan = 'full';

    public function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'صباح الخير';
        }
        if ($hour < 17) {
            return 'طاب يومك';
        }

        return 'مساء الخير';
    }

    public function getUserName(): string
    {
        return Auth::user()->name;
    }

    public function getArabicDate(): string
    {
        return now()->locale('ar')->isoFormat('dddd، D MMMM YYYY');
    }

    public function getPulseMetrics(): array
    {
        $todayCount = 0;
        $yesterdayCount = 0;
        $diff = 0;
        $percentage = 0;
        $unreadWhatsapp = 0;
        $pendingBookings = 0;

        if (class_exists(Booking::class)) {
            $todayCount = Booking::whereDate('booking_date', now())->count();
            $yesterdayCount = Booking::whereDate('booking_date', now()->subDay())->count();
            $diff = $todayCount - $yesterdayCount;
            $percentage = $yesterdayCount > 0 ? round(($diff / $yesterdayCount) * 100) : ($todayCount > 0 ? 100 : 0);
            $pendingBookings = Booking::where('status', 'new')->count();
        }

        if (class_exists(WhatsappLog::class)) {
            $unreadWhatsapp = WhatsappLog::where('is_read', false)->count();
        }

        return [
            'bookings_today' => $todayCount,
            'trend' => $diff >= 0 ? 'up' : 'down',
            'percentage' => abs($percentage).'%',
            'unread_whatsapp' => $unreadWhatsapp,
            'pending_bookings' => $pendingBookings,
        ];
    }
}
