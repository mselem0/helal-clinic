<?php

namespace Modules\PatientCore\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\PatientCore\Livewire\PatientAuth;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PatientCoreServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'PatientCore';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'patientcore';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        if (class_exists(Livewire::class)) {
            Livewire::component('patient-auth', PatientAuth::class);
        }
    }
}
