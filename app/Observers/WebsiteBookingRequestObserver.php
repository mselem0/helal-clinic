<?php

namespace App\Observers;

use App\Models\WebsiteBookingRequest;
use App\Models\WebsiteBookingSheetSync;
use App\Services\GoogleSheets\BookingSheetsSync;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WebsiteBookingRequestObserver implements ShouldHandleEventsAfterCommit
{
    public function saved(WebsiteBookingRequest $booking): void
    {
        // The booking is already committed. Provider/configuration failures must never affect it.
        try {
            WebsiteBookingSheetSync::firstOrCreate(['website_booking_request_id' => $booking->id])->update(['status' => 'pending']);
            app(BookingSheetsSync::class)->syncRequest($booking);
        } catch (\Throwable) { /* Local booking remains durable; scheduler retries the full set. */
        }
    }
}
