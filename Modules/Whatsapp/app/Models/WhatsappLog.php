<?php

// app/Models/WhatsappLog.php

namespace Modules\Whatsapp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Booking\Models\Booking;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsappLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'patient_name',
        'patient_phone',
        'message_content',
        'source',
        'booking_id',
        'template_id',
        'status',
        'admin_note',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id',
                'patient_name',
                'patient_phone',
                'message_content',
                'source',
                'booking_id',
                'template_id',
                'status',
                'admin_note',
                'is_read',
            ])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }
}
