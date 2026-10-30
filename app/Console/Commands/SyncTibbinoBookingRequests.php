<?php

namespace App\Console\Commands;

use App\Models\WebsiteBookingRequest;
use App\Services\TibbinoBooking;
use Illuminate\Console\Command;

class SyncTibbinoBookingRequests extends Command
{
    protected $signature = 'tibbino:sync-booking-requests {--limit=50 : Maximum requests per run}';

    protected $description = 'Retry due website request mirrors without creating appointments';

    public function handle(TibbinoBooking $mirror): int
    {
        if (! TibbinoBooking::enabled()) {
            $this->info('Tibbino request sync is disabled or unconfigured.');

            return self::SUCCESS;
        }

        // A worker killed during its final attempt cannot leave a permanent live lease.
        WebsiteBookingRequest::where('integration_state', 'syncing')
            ->where('sync_attempts', '>=', TibbinoBooking::MAX_ATTEMPTS)
            ->where('last_sync_attempt_at', '<=', now()->subMinute())
            ->update(['integration_state' => 'failed', 'last_sync_error' => 'Final attempt interrupted; review required', 'next_sync_attempt_at' => null]);

        // Materialize a bounded batch so failures cannot be retried twice in this run.
        $ids = TibbinoBooking::due()->orderBy('id')->limit(max(1, min(200, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            $mirror->sync($id);
        }
        $this->info('Processed '.$ids->count().' due request(s).');

        return self::SUCCESS;
    }
}
