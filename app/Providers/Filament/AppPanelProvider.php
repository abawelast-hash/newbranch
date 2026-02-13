<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\EmployeeDashboard;
use App\Filament\App\Widgets\EmployeeWelcomeWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Models\Setting;

/**
 * SARH v1.9.0 — بوابة الموظفين /app
 *
 * Module 5: Employee Portal Emerald Theme
 * Module 4: Mobile-First responsive configuration
 *
 * بوابة مستقلة تماماً عن /admin، مخصصة للموظفين (security_level < 4).
 * تكتشف Resources/Pages/Widgets من مجلد Filament/App/ فقط.
 */
class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => [
                    50  => '#ecfdf5',
                    100 => '#d1fae5',
                    200 => '#a7f3d0',
                    300 => '#6ee7b7',
                    400 => '#34d399',
                    500 => '#10b981',
                    600 => '#059669',
                    700 => '#047857',
                    800 => '#065f46',
                    900 => '#064e3b',
                    950 => '#022c22',
                ],
                'danger'  => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info'    => Color::Sky,
                'gray'    => Color::Zinc,
            ])
            ->font('Cairo')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandName(fn () => Setting::instance()->app_name . ' — بوابة الموظفين')
            ->brandLogo(fn () => Setting::instance()->logo_url)
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => Setting::instance()->favicon_url)
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(
                in: app_path('Filament/App/Resources'),
                for: 'App\\Filament\\App\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/App/Pages'),
                for: 'App\\Filament\\App\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/App/Widgets'),
                for: 'App\\Filament\\App\\Widgets'
            )
            ->pages([
                EmployeeDashboard::class,
            ])
            ->widgets([
                EmployeeWelcomeWidget::class,
            ])
            ->authGuard('web')
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
            ->databaseNotifications()
            ->spa()
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.app.partials.geolocation-script'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#FF8C00">'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('filament.components.pwa-install-button'),
            );
    }
}
