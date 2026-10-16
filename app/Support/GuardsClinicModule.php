<?php

namespace App\Support;

trait GuardsClinicModule
{
    public static function canAccess(): bool
    {
        $module = explode('\\', static::class)[1];
        if (! ClinicFeatures::enabled($module) || ! auth()->check()) {
            return false;
        }
        if (is_subclass_of(static::class, \Filament\Resources\Resource::class)) {
            return static::canViewAny();
        }

        return auth()->user()->hasRole('super_admin') || auth()->user()->can('page_'.class_basename(static::class));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
