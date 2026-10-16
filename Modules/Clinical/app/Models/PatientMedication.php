<?php

namespace Modules\Clinical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PatientCore\Models\Patient;

class PatientMedication extends Model
{
    use HasFactory;

    protected $table = 'patient_medications';

    protected $fillable = [
        'patient_id',
        'medication_name',
        'dosage',
        'frequency',
        'start_date',
        'end_date',
        'prescribed_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
