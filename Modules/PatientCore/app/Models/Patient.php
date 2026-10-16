<?php

namespace Modules\PatientCore\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'date_of_birth',
        'gender',
        'national_id',
        'blood_type',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany('\Modules\Booking\Models\Booking', 'patient_id');
    }

    public function medicalHistoryItems(): HasMany
    {
        return $this->hasMany('\Modules\Clinical\Models\MedicalHistoryItem');
    }

    public function visitRecords(): HasMany
    {
        return $this->hasMany('\Modules\PatientCore\Models\VisitRecord');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany('\Modules\Clinical\Models\Prescription');
    }

    public function patientMedications(): HasMany
    {
        return $this->hasMany('\Modules\Clinical\Models\PatientMedication');
    }

    public function currentMedications(): HasMany
    {
        return $this->patientMedications()->where('is_active', true);
    }

    /**
     * Calculate patient's age in years.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Get the latest visit record for the patient.
     */
    public function getLastVisitAttribute()
    {
        return $this->visitRecords()->latest('visit_date')->first();
    }
}
