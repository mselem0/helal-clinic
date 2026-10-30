<?php

namespace App\Jobs;

use App\Services\TibbinoBooking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncTibbinoBookingRequest implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 15;

    public function __construct(public int $requestId) {}

    public function handle(TibbinoBooking $mirror): void
    {
        $mirror->sync($this->requestId);
    }
}
