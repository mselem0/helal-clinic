<?php

// app/Models/DoctorCredential.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCredential extends Model
{
    protected $fillable = [
        'about_doctor_id',
        'type',
        'title',
        'institution',
        'year',
        'order',
    ];

    public function aboutDoctor(): BelongsTo
    {
        return $this->belongsTo(AboutDoctor::class);
    }
}
