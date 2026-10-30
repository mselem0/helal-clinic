<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WebsiteBookingRequest extends Model
{
    public const VISIT_TYPES = \App\Support\BookingVisitDetails::TYPES;

    public const STATUSES = ['new' => 'جديد', 'contacted' => 'تم التواصل', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغى'];

    public const INTEGRATION_STATES = ['pending' => 'بانتظار إرسال الطلب', 'syncing' => 'جارٍ إرسال الطلب', 'failed' => 'تعذر إرسال الطلب', 'synced' => 'تم إرسال الطلب إلى Tibbino', 'submitting' => 'ربط سابق — يلزم المراجعة', 'uncertain' => 'نتيجة ربط سابق غير مؤكدة', 'rejected' => 'رفض النظام الطلب'];

    protected static function booted(): void
    {
        static::creating(function (self $booking) {
            $booking->request_key ??= (string) Str::uuid();
        });
    }

    public function sheetSync(): HasOne
    {
        return $this->hasOne(WebsiteBookingSheetSync::class);
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['booking_date' => 'date', 'visit_window_acknowledged_at' => 'datetime', 'sync_attempts' => 'integer', 'last_sync_attempt_at' => 'datetime', 'next_sync_attempt_at' => 'datetime', 'synced_at' => 'datetime'];
    }
}
