<?php

namespace Modules\Clinical\Policies;

use App\Support\ClinicModulePolicy;

class PatientMedicationPolicy extends ClinicModulePolicy
{
    protected string $resource = 'patient_medication';
}
