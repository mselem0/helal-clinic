<?php

namespace Modules\Clinical\Policies;

use App\Support\ClinicModulePolicy;

class MedicalHistoryItemPolicy extends ClinicModulePolicy
{
    protected string $resource = 'medical_history_item';
}
