<?php

use App\Http\Middleware\HandleSeoRedirects;
use App\Http\Middleware\InjectSeoMeta;
use App\Http\Middleware\PreventAdsIndexing;
use App\Http\Middleware\Track404Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        $middleware->prepend(PreventAdsIndexing::class);
        $middleware->append(HandleSeoRedirects::class);
        $middleware->append(Track404Middleware::class);

        $middleware->web(append: [
            InjectSeoMeta::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception responses are rendered outside the middleware pipeline.
        $exceptions->respond(function ($response) {
            $response->headers->set('X-Robots-Tag', PreventAdsIndexing::HEADER);

            return $response;
        });
    })->create();

if (getenv('CLINIC_E2E') === '1' && getenv('APP_ENV') === 'testing') {
    $application->useStoragePath(getenv('LARAVEL_STORAGE_PATH'));
}

return $application;
