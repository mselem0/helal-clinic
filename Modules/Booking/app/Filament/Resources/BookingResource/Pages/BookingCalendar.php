<?php

// app/Filament/Resources/BookingResource/Pages/BookingCalendar.php

namespace Modules\Booking\Filament\Resources\BookingResource\Pages;

use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Modules\Booking\Filament\Resources\BookingResource;
use Modules\Booking\Models\Booking;

class BookingCalendar extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.resources.booking-resource.pages.booking-calendar';

    protected static ?string $title = 'تقويم المواعيد';

    public $currentMonth;

    public $currentYear;

    public $days = [];

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->calculateCalendar();
    }

    public function calculateCalendar()
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $date = $startOfMonth->copy()->startOfWeek(Carbon::SATURDAY);
        $end = $endOfMonth->copy()->endOfWeek(Carbon::FRIDAY);

        $this->days = [];
        while ($date <= $end) {
            $this->days[] = [
                'date' => $date->copy(),
                'is_current_month' => $date->month == $this->currentMonth,
                'bookings' => Booking::whereDate('booking_date', $date->format('Y-m-d'))->get(),
            ];
            $date->addDay();
        }
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->calculateCalendar();
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
        $this->calculateCalendar();
    }
}
