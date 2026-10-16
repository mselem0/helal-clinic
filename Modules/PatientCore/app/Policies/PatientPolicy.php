<?php

namespace Modules\PatientCore\Policies;

use App\Support\ClinicModulePolicy;

class PatientPolicy extends ClinicModulePolicy
{
    protected string $resource = 'patient';
}
