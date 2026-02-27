<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\EmployeeDashboard;
use App\Filament\App\Widgets\EmployeeWelcomeWidget;
use App\Filament\Pages\Auth\CustomLogin;
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
            ->login(CustomLogin::class)
            ->passwordReset()
            ->colors([
                // SARH Neo: Teal Employee Portal Theme
                'primary' => [
                    50  => '#F0FDFA',
                    100 => '#CCFBF1',
                    200 => '#99F6E4',
                    300 => '#5EEAD4',
                    400 => '#2DD4BF',
                    500 => '#14B8A6',
                    600 => '#0D9488',
                    700 => '#0F766E',
                    800 => '#115E59',
                    900 => '#134E4A',
                    950 => '#042F2E',
                ],
                'danger'  => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info'    => Color::Sky,
                'gray'    => [
                    50  => '#F8FAFC',
                    100 => '#F1F5F9',
                    200 => '#E2E8F0',
                    300 => '#CBD5E1',
                    400 => '#94A3B8',
                    500 => '#64748B',
                    600 => '#475569',
                    700 => '#334155',
                    800 => '#1E293B',
                    900 => '#0F172A',
                    950 => '#020617',
                ],
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
                fn () => new HtmlString('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#0F172A">'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.components.arabic-numerals'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('filament.components.pwa-install-button'),
            );
    }
}
