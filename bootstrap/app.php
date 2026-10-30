<?php

use App\Http\Middleware\HandleSeoRedirects;
use App\Http\Middleware\InjectSeoMeta;
use App\Http\Middleware\Track404Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Livewire\Exceptions\RootTagMissingFromViewException;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        $middleware->append(HandleSeoRedirects::class);
        $middleware->append(Track404Middleware::class);

        $middleware->web(append: [
            InjectSeoMeta::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (RootTagMissingFromViewException $exception): void {
            $component = rescue(fn () => app('livewire')->current(), null, report: false);

            logger()->warning('Livewire rendered a component without an HTML root tag.', [
                'route' => request()->route()?->getName(),
                'method' => request()->method(),
                'component' => $component ? $component::class : null,
                'component_name' => $component && method_exists($component, 'getName') ? $component->getName() : null,
                'view' => $component && is_callable([$component, 'getView']) ? $component->getView() : null,
            ]);
        });
    })->create();

if (getenv('CLINIC_E2E') === '1' && getenv('APP_ENV') === 'testing') {
    $application->useStoragePath(getenv('LARAVEL_STORAGE_PATH'));
}

return $application;
