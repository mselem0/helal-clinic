<?php

// app/Models/Booking.php

namespace Modules\Booking\Models;

use App\Models\ClinicBranch;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PatientCore\Models\Patient;
use Modules\Whatsapp\Models\WhatsappLog;
use Nwidart\Modules\Facades\Module;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'patient_id',
        'patient_name',
        'patient_phone',
        'booking_date',
        'booking_time',
        'service_id',
        'clinic_branch_id',
        'patient_condition',
        'postponed_reason',
        'is_alert_seen',
        'status',
        'is_read',
        'source',
        'medium',
        'campaign',
        'notes',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_alert_seen' => 'boolean',
        'booking_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function clinicBranch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class);
    }

    protected static function booted()
    {
        parent::booted(); // Important for trait booting

        static::creating(function ($booking) {
            if (class_exists(Patient::class)) {
                $patient = Patient::where('phone', $booking->patient_phone)->first();
                if ($patient) {
                    $booking->patient_id = $patient->id;
                }
            }
        });

        static::created(function ($booking) {
            if (! Module::find('Whatsapp')?->isEnabled()) {
                return;
            }
            if (class_exists(WhatsappLog::class)) {
                WhatsappLog::create([
                    'user_id' => $booking->user_id,
                    'patient_name' => $booking->patient_name,
                    'patient_phone' => $booking->patient_phone,
                    'message_content' => 'حجز جديد: '.$booking->patient_name.' بتاريخ '.$booking->booking_date->format('Y-m-d'),
                    'source' => 'booking_form',
                    'booking_id' => $booking->id,
                    'status' => 'new',
                ]);
            }
        });
    }
}
