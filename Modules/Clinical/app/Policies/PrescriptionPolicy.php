<?php

namespace Modules\Clinical\Policies;

use App\Support\ClinicModulePolicy;

class PrescriptionPolicy extends ClinicModulePolicy
{
    protected string $resource = 'prescription';
}
