<?php

namespace Modules\Clinical\Policies;

use App\Support\ClinicModulePolicy;

class PrintTemplatePolicy extends ClinicModulePolicy
{
    protected string $resource = 'print_template';
}
