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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * SARH v1.9.0 — بوابة الموظفين /app
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
                'primary' => Color::Emerald,
                'danger'  => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info'    => Color::Sky,
                'gray'    => Color::Zinc,
            ])
            ->font('Cairo')
            ->brandName('سهر — بوابة الموظفين')
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
                'panels::body.end',
                fn () => view('filament.app.partials.geolocation-script'),
            );
    }
}
