<?php

namespace App\Services;

use App\Models\WebsiteBookingRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TibbinoBooking
{
    public const MAX_ATTEMPTS = 5;

    public static function enabled(): bool
    {
        return (bool) config('services.tibbino.enabled') && filled(config('services.tibbino.url')) && filled(config('services.tibbino.token'));
    }

    public static function due(): Builder
    {
        return WebsiteBookingRequest::query()
            ->whereNotNull('request_key')
            ->where('sync_attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('integration_state', 'pending')
                        ->where(fn (Builder $query) => $query->whereNull('next_sync_attempt_at')->orWhere('next_sync_attempt_at', '<=', now()));
                })->orWhere(function (Builder $query): void {
                    $query->where('integration_state', 'failed')->whereNotNull('next_sync_attempt_at')->where('next_sync_attempt_at', '<=', now());
                })->orWhere(function (Builder $query): void {
                    // Recover a process that stopped after claiming a request.
                    $query->where('integration_state', 'syncing')->where('last_sync_attempt_at', '<=', now()->subMinute());
                });
            });
    }

    public function sync(int $id): void
    {
        if (! self::enabled()) {
            return;
        }
        // An atomic claim prevents the queue and scheduler sending simultaneously.
        $claimed = self::due()->whereKey($id)->update([
            'integration_state' => 'syncing',
            'sync_attempts' => DB::raw('sync_attempts + 1'),
            'last_sync_attempt_at' => now(),
            'next_sync_attempt_at' => null,
        ]);
        if (! $claimed) {
            return;
        }
        $receipt = WebsiteBookingRequest::findOrFail($id);
        $payload = [
            'request_key' => $receipt->request_key,
            'patient_name' => $receipt->patient_name,
            'phone' => $receipt->patient_phone,
            'date' => $receipt->booking_date->toDateString(),
            'service_name' => $receipt->service_name,
            'branch_name' => $receipt->branch_name,
            'source_host' => $receipt->source_host,
        ];
        try {
            $response = Http::baseUrl(rtrim(config('services.tibbino.url'), '/').'/api/v1/public')
                ->withToken(config('services.tibbino.token'))->acceptJson()->asJson()
                ->connectTimeout(2)->timeout(5)->withoutRedirecting()
                ->post('booking-requests', $payload);
            $remoteId = $response->json('data.request_id');
            if (in_array($response->status(), [200, 201], true)
                && (is_int($remoteId) || (is_string($remoteId) && ctype_digit($remoteId))) && (int) $remoteId > 0
                && $response->json('data.status') === 'pending'
                && $response->json('data.date') === $payload['date']
                && array_key_exists('time', $response->json('data') ?? []) && $response->json('data.time') === null) {
                $receipt->update(['remote_request_id' => $remoteId, 'integration_state' => 'synced', 'synced_at' => now(), 'last_sync_error' => null, 'next_sync_attempt_at' => null]);

                return;
            }
            // Never persist upstream response bodies, which can contain sensitive data.
            $terminal = $response->clientError() && ! in_array($response->status(), [408, 429], true);
            $error = 'HTTP '.$response->status();
        } catch (\Throwable $exception) {
            $terminal = false;
            $error = 'Connection or response failure';
        }
        $receipt->update([
            'integration_state' => 'failed', 'last_sync_error' => $error,
            'next_sync_attempt_at' => ! $terminal && $receipt->sync_attempts < self::MAX_ATTEMPTS
                ? now()->addSeconds(60 * (2 ** ($receipt->sync_attempts - 1))) : null,
        ]);
    }
}
