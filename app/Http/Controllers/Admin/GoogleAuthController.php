<?php

// app/Http/Controllers/Admin/GoogleAuthController.php

namespace App\Http\Controllers\Admin;

use App\Helpers\SeoHelper;
use App\Http\Controllers\Controller;
use Filament\Notifications\Notification;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class GoogleAuthController extends Controller
{
    public function connect()
    {
        $client = $this->getGoogleClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        if (! $request->has('code')) {
            Notification::make()->title('فشل الاتصال بجوجل')->danger()->send();

            $targetRoute = Route::has('filament.admin.pages.seo-management')
                ? route('filament.admin.pages.seo-management', ['tab' => 'settings'])
                : route('filament.admin.pages.dashboard');

            return redirect()->to($targetRoute);
        }

        $client = $this->getGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            Notification::make()->title('خطأ في الرمز التعريفي')->danger()->send();

            $targetRoute = Route::has('filament.admin.pages.seo-management')
                ? route('filament.admin.pages.seo-management', ['tab' => 'settings'])
                : route('filament.admin.pages.dashboard');

            return redirect()->to($targetRoute);
        }

        // Save tokens
        SeoHelper::setSetting('google_access_token', json_encode($token), 'verification');

        if (isset($token['refresh_token'])) {
            SeoHelper::setSetting('google_refresh_token', $token['refresh_token'], 'verification');
        }

        Notification::make()->title('تم ربط حساب جوجل بنجاح')->success()->send();

        $targetRoute = Route::has('filament.admin.pages.seo-management')
            ? route('filament.admin.pages.seo-management', ['tab' => 'settings'])
            : route('filament.admin.pages.dashboard');

        return redirect()->to($targetRoute);
    }

    protected function getGoogleClient()
    {
        $client = new Client;
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));

        // Use the redirect URI from config (from .env) if set, otherwise fallback to the route
        $redirectUri = config('services.google.redirect') ?: route('admin.google.callback');
        $client->setRedirectUri($redirectUri);

        $client->addScope('https://www.googleapis.com/auth/analytics.readonly');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        return $client;
    }
}
