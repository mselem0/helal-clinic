<?php

namespace App\Support;

use Nwidart\Modules\Facades\Module;

class ClinicFeatures
{
    public const DEPENDENCIES = ['Booking' => ['PatientCore'], 'Clinical' => ['PatientCore'], 'Whatsapp' => ['Booking'], 'AI' => ['PatientCore', 'Booking', 'Clinical']];

    public static function enabled(string $name): bool
    {
        if (! class_exists(Module::class) || ! Module::find($name)?->isEnabled()) {
            return false;
        }
        foreach (self::DEPENDENCIES[$name] ?? [] as $dependency) {
            if (! self::enabled($dependency)) {
                return false;
            }
        }

        return true;
    }
}
