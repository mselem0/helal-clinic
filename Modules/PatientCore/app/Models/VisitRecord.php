<?php

namespace Modules\PatientCore\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'booking_id',
        'visit_date',
        'chief_complaint',
        'diagnosis',
        'examination_notes',
        'treatment_plan',
        'follow_up_date',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo('\Modules\Booking\Models\Booking');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany('\Modules\Clinical\Models\Prescription');
    }
}
