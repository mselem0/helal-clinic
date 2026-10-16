<?php

// app/Filament/Pages/Auth/Login.php

namespace App\Filament\Pages\Auth;

use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function mount(): void
    {
        parent::mount();

        $this->maxWidth = 'full';
    }

    public function authenticate(): ?LoginResponse
    {
        // 1. Rate Limiting: 5 attempts per 15 minutes
        $rateLimitKey = 'login-attempt:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            activity('auth')
                ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
                ->log('محاولة دخول محظورة بسبب تخطي حد المحاولات');

            throw ValidationException::withMessages([
                'data.email' => __('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        // 2. reCAPTCHA Verification (only when API key is configured)
        if (filled(config('recaptcha.api_site_key'))) {
            $recaptchaToken = request()->input('g-recaptcha-response');

            if (empty($recaptchaToken) || ! recaptcha()->verify($recaptchaToken)->isSuccess()) {
                RateLimiter::hit($rateLimitKey, 900); // 15 minutes

                activity('auth')
                    ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
                    ->log('فشل التحقق من reCAPTCHA');

                throw ValidationException::withMessages([
                    'data.email' => 'الرجاء التحقق من اختبار الأمان (reCAPTCHA) بشكل صحيح.',
                ]);
            }
        }

        try {
            $response = parent::authenticate();

            // Log successful login
            activity('auth')
                ->performedOn(auth()->user())
                ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
                ->log('تسجيل دخول ناجح');

            auth()->user()->update(['last_login_at' => now()]);

            // Clear attempts on success
            RateLimiter::clear($rateLimitKey);

            return $response;
        } catch (ValidationException $e) {
            RateLimiter::hit($rateLimitKey, 900); // 15 minutes

            activity('auth')
                ->withProperties(['email' => $this->form->getState()['email'] ?? 'unknown', 'ip' => request()->ip()])
                ->log('محاولة دخول فاشلة (بيانات خاطئة)');

            throw $e;
        }
    }
}
