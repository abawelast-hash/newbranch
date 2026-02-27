<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomLogin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
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
 * SARH v1.9.0 — لوحة الإدارة /admin
 *
 * Module 5: Corporate Brand Identity — Orange (#FF8C00), Black, White/Grey
 * Module 3: Stealth visibility via dynamic navigation
 * Module 4: Mobile-First responsive configuration
 *
 * متاحة فقط لـ security_level >= 4 أو is_super_admin.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(CustomLogin::class)
            ->brandName(fn () => Setting::instance()->app_name)
            ->brandLogo(fn () => Setting::instance()->logo_url)
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => Setting::instance()->favicon_url)
            ->colors([
                // SARH Neo: Violet-Indigo Admin Theme
                'primary' => [
                    50  => '#F5F3FF',
                    100 => '#EDE9FE',
                    200 => '#DDD6FE',
                    300 => '#C4B5FD',
                    400 => '#A78BFA',
                    500 => '#8B5CF6',
                    600 => '#7C3AED',
                    700 => '#6D28D9',
                    800 => '#5B21B6',
                    900 => '#4C1D95',
                    950 => '#2E1065',
                ],
                'danger'  => Color::Red,
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Dashboard is auto-discovered from App\Filament\Pages\Dashboard
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
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
                \App\Http\Middleware\EnsureAdminPanelAccess::class,
            ])
            ->databaseNotifications()
            ->spa()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString('<link rel="manifest" href="/manifest.json"><meta name="theme-color" content="#4C1D95">'),

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
