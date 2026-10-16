<?php

namespace Modules\AI\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\AI\Livewire\AiChatWidget;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AIServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'AI';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'ai';

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
            Livewire::component('ai-chat-widget', AiChatWidget::class);
        }
    }
}
