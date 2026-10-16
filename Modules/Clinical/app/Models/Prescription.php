<?php

namespace Modules\Clinical\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PatientCore\Models\Patient;
use Modules\PatientCore\Models\VisitRecord;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'visit_record_id',
        'prescribed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'prescribed_at' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
