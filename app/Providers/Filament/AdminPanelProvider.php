<?php

// app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SiteSettings;
use App\Filament\Pages\ThemeEditor;
use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\ClinicBranchResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\SitePageResource;
use App\Filament\Resources\TestimonialResource;
use App\Filament\Resources\TvInterviewResource;
use App\Filament\Resources\VideoResource;
use App\Filament\Resources\WebsiteBookingRequestResource;
use App\Models\SitePage;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->font('IBM Plex Sans Arabic', url: asset('fonts/ibm-plex-arabic.css'))
            ->brandName('عيادات هلال | لوحة الإدارة')
            ->favicon(asset('assets/logo.png'))
            ->darkMode(false)
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
            ->homeUrl(fn (): string => WebsiteBookingRequestResource::canViewAny() ? WebsiteBookingRequestResource::getUrl() : ThemeEditor::getUrl())
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $this->contentNavigation($builder))
            ->spa()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
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
            ]);

        if (class_exists(Module::class)) {
            foreach (Module::allEnabled() as $module) {
                $panel->discoverResources(in: $module->getPath().'/app/Filament/Resources', for: 'Modules\\'.$module->getName().'\\Filament\\Resources');
                $panel->discoverPages(in: $module->getPath().'/app/Filament/Pages', for: 'Modules\\'.$module->getName().'\\Filament\\Pages');
            }
        }

        return $panel;
    }

    private function contentNavigation(NavigationBuilder $builder): NavigationBuilder
    {
        $items = [NavigationItem::make('الحجوزات')->icon('heroicon-o-inbox')
            ->url(fn () => WebsiteBookingRequestResource::getUrl())
            ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.website-booking-requests.*'))
            ->visible(fn () => WebsiteBookingRequestResource::canViewAny())];
        foreach ([
            [ServiceResource::class, 'الخدمات', 'heroicon-o-heart'],
            [ArticleResource::class, 'المقالات', 'heroicon-o-document-text'],
            [ClinicBranchResource::class, 'الفروع', 'heroicon-o-map-pin'],
            [VideoResource::class, 'الفيديوهات', 'heroicon-o-play-circle'],
            [TvInterviewResource::class, 'اللقاءات التلفزيونية', 'heroicon-o-tv'],
            [TestimonialResource::class, 'آراء المريضات', 'heroicon-o-chat-bubble-left-right'],
        ] as [$resource, $label, $icon]) {
            if ($resource::canViewAny()) {
                $items[] = NavigationItem::make($label)->icon($icon)->url($resource::getUrl())
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.'.$resource::getSlug().'.*'));
            }
        }
        if (SiteSettings::canAccess()) {
            $items[] = NavigationItem::make('بيانات التواصل')->icon('heroicon-o-phone')->url(SiteSettings::getUrl())
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.site-settings'));
        }
        $builder->items($items);
        if (SitePageResource::canViewAny()) {
            $pages = [];
            foreach (SitePage::orderBy('id')->get() as $page) {
                $pages[] = NavigationItem::make($page->title)->url(SitePageResource::getUrl('edit', ['record' => $page]))
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.site-pages.edit') && (string) request()->route('record') === (string) $page->id);
            }
            $pages[] = NavigationItem::make('كل الصفحات')->url(SitePageResource::getUrl())
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.site-pages.index', 'filament.admin.resources.site-pages.create'));
            $pages[] = NavigationItem::make('نصوص وصور الأقسام')->url(ThemeEditor::getUrl())
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.theme-editor'));
            $builder->groups([NavigationGroup::make('صفحات الموقع')->collapsible()->collapsed()->items($pages)]);
        }

        return $builder;
    }
}
