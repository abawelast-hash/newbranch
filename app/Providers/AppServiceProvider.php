<?php

namespace App\Providers;

use App\Events\AttendanceRecorded;
use App\Events\BadgeAwarded;
use App\Listeners\HandleAttendanceRecorded;
use App\Listeners\HandleBadgePoints;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Circular;
use App\Models\Leave;
use App\Models\ReportFormula;
use App\Models\User;
use App\Policies\AttendanceLogPolicy;
use App\Policies\BranchPolicy;
use App\Policies\CircularPolicy;
use App\Policies\LeavePolicy;
use App\Policies\ReportPolicy;
use App\Policies\UserPolicy;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // StreakService — singleton لتجنب إعادة بناء الكاش في نفس الطلب
        $this->app->singleton(\App\Services\StreakService::class);

        // SafeFormulaEngine — singleton آمن (بلا eval) لمحرك الصيغ
        $this->app->singleton(\App\Services\SafeFormulaEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |----------------------------------------------------------------------
        | Event → Listener Bindings (v4.0)
        |----------------------------------------------------------------------
        */
        Event::listen(BadgeAwarded::class, HandleBadgePoints::class);
        Event::listen(AttendanceRecorded::class, HandleAttendanceRecorded::class);

        /*
        |----------------------------------------------------------------------
        | Scramble API Docs — include attendance/traps/telemetry routes
        |----------------------------------------------------------------------
        */
        Scramble::routes(function (RoutingRoute $route) {
            return str_starts_with($route->uri, 'attendance')
                || str_starts_with($route->uri, 'telemetry');
        });

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

        /*
        |----------------------------------------------------------------------
        | Register Policies (v4.0-emergency)
        |----------------------------------------------------------------------
        */
        Gate::policy(User::class,          UserPolicy::class);
        Gate::policy(AttendanceLog::class,  AttendanceLogPolicy::class);
        Gate::policy(Circular::class,       CircularPolicy::class);
        Gate::policy(Branch::class,         BranchPolicy::class);
        Gate::policy(ReportFormula::class,  ReportPolicy::class);
        Gate::policy(Leave::class,          LeavePolicy::class);
    }
}
