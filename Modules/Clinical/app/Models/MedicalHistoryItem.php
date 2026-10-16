<?php

namespace Modules\Clinical\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PatientCore\Models\Patient;

class MedicalHistoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'type',
        'title',
        'description',
        'diagnosed_at',
        'is_active',
        'severity',
        'notes',
    ];

    protected $casts = [
        'diagnosed_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
