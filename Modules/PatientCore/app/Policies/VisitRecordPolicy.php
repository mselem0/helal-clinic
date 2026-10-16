<?php

namespace Modules\PatientCore\Policies;

use App\Support\ClinicModulePolicy;

class VisitRecordPolicy extends ClinicModulePolicy
{
    protected string $resource = 'visit_record';
}
