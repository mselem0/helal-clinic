<?php

// app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Widgets\GreetingHeaderWidget;
use App\Filament\Widgets\QuickActionsWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Nwidart\Modules\Facades\Module;
use TomatoPHP\FilamentMediaManager\FilamentMediaManagerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('codeAiAdminDash')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class)
            ->colors([
                'primary' => Color::hex('#006f76'),
                'secondary' => Color::hex('#ed8b19'),
                'gray' => Color::Slate,
            ])
            ->font('Tajawal', url: asset('fonts/tajawal.css'))
            ->brandName('عيادات هلال | لوحة الإدارة')
            ->favicon(asset('storage/favicon.ico'))
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/app.css')
            ->icons([
                'panels::sidebar.collapse-button' => 'heroicon-m-chevron-double-left',
                'panels::sidebar.collapse-button.rtl' => 'heroicon-m-chevron-double-right',
                'panels::sidebar.expand-button' => 'heroicon-m-chevron-double-right',
                'panels::sidebar.expand-button.rtl' => 'heroicon-m-chevron-double-left',
            ])
            ->renderHook(PanelsRenderHook::BODY_START, fn (): string => view('filament.components.mobile-sidebar')->render())
            ->renderHook(
                PanelsRenderHook::TOPBAR_START,
                fn (): string => view('filament.components.topbar-greeting')->render(),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.components.topbar-shortcuts')->render(),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): string => view('filament.components.footer')->render(),
            )
            ->spa()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets(array_filter([
                GreetingHeaderWidget::class,
                QuickActionsWidget::class,
                (class_exists(Module::class) && Module::find('Booking')?->isEnabled() && class_exists('\Modules\Booking\Filament\Widgets\BookingStats')) ? '\Modules\Booking\Filament\Widgets\BookingStats' : null,
                (class_exists(Module::class) && Module::find('Booking')?->isEnabled() && class_exists('\Modules\Booking\Filament\Widgets\BookingsBySourceChart')) ? '\Modules\Booking\Filament\Widgets\BookingsBySourceChart' : null,
                (class_exists(Module::class) && Module::find('Booking')?->isEnabled() && class_exists('\Modules\Booking\Filament\Widgets\RecentBookingsTable')) ? '\Modules\Booking\Filament\Widgets\RecentBookingsTable' : null,
            ]))
            ->databaseNotifications()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentMediaManagerPlugin::make()
                    ->allowSubFolders(),
                FilamentShieldPlugin::make(),
                BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: true)
                    ->enableTwoFactorAuthentication(),
            ])
            ->renderHook(
                'panels::body.end',
                fn (): string => (auth()->check() && config('clinic-integrations.ai') && class_exists(Module::class) && Module::find('AI')?->isEnabled() && class_exists('\Modules\AI\Livewire\AiChatWidget')) ? Blade::render("@livewire('ai-chat-widget')") : '',
            );

        if (class_exists(Module::class)) {
            foreach (Module::allEnabled() as $module) {
                $panel->discoverResources(in: $module->getPath().'/app/Filament/Resources', for: 'Modules\\'.$module->getName().'\\Filament\\Resources');
                $panel->discoverPages(in: $module->getPath().'/app/Filament/Pages', for: 'Modules\\'.$module->getName().'\\Filament\\Pages');
                $panel->discoverWidgets(in: $module->getPath().'/app/Filament/Widgets', for: 'Modules\\'.$module->getName().'\\Filament\\Widgets');
            }
        }

        return $panel;
    }
}
