<?php

// routes/console.php

use App\Services\GoogleSheets\BookingSheetsSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('tibbino:sync-booking-requests')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:sync-sheets', function (BookingSheetsSync $sync) {
    $result = $sync->syncNow();
    $this->info("Synced: {$result['synced']}; failed: {$result['failed']}".(! empty($result['busy']) ? '; another sync is running' : ''));

    return $result['failed'] ? 1 : 0;
})->purpose('Synchronize website booking requests with the configured Google Sheet');

Schedule::command('bookings:sync-sheets')->everyMinute()->withoutOverlapping();
