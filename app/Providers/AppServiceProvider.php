<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |----------------------------------------------------------------------
        | Arabic Numeral Blade Directive: @arNum($value)
        |----------------------------------------------------------------------
        */
        Blade::directive('arNum', function ($expression) {
            return "<?php echo \App\Helpers\ArabicHelper::toArabicDigits($expression); ?>";
        });

        /*
        |----------------------------------------------------------------------
        | Level 10 "God Mode" — Absolute Authority Gate
        |----------------------------------------------------------------------
        | Any user with security_level === 10 bypasses ALL authorization gates.
        | This includes:
        |   - Geofencing bypass for attendance
        |   - Full unencrypted Whistleblower Vault access
        |   - Full Trap Audit Log access
        |   - All resource CRUD operations
        |----------------------------------------------------------------------
        */
        Gate::before(function ($user, $ability) {
            if ($user->security_level === 10 || $user->is_super_admin) {
                return true;
            }
        });

        /*
        |----------------------------------------------------------------------
        | Named Gates for Level 10 Vault Access
        |----------------------------------------------------------------------
        */
        Gate::define('access-whistleblower-vault', function ($user) {
            return $user->security_level >= 10;
        });

        Gate::define('access-trap-audit', function ($user) {
            return $user->security_level >= 10;
        });

        Gate::define('bypass-geofence', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        /*
        |----------------------------------------------------------------------
        | Competition Engine Gates (v1.7.0)
        |----------------------------------------------------------------------
        */
        Gate::define('manage-competition', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        Gate::define('adjust-points', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        /*
        |----------------------------------------------------------------------
        | Module 2: Enhanced RBAC & Module 3: Stealth Visibility Gates
        |----------------------------------------------------------------------
        */
        Gate::define('manage-roles', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        Gate::define('manage-permissions', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        Gate::define('manage-attendance-exceptions', function ($user) {
            return $user->security_level >= 7 || $user->is_super_admin;
        });

        Gate::define('manage-score-adjustments', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        Gate::define('manage-report-formulas', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });

        Gate::define('access-stealth-resources', function ($user) {
            return $user->security_level >= 10 || $user->is_super_admin;
        });
    }
}
