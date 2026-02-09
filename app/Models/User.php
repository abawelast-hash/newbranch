<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * SARH v1.9.0 — Identity Isolation بين بوابة الإدارة وبوابة الموظفين.
     *
     * /admin → is_super_admin أو (security_level >= 4 + حساب مفعّل)
     * /app   → security_level < 4 + حساب مفعّل (الموظفون العاديون فقط)
     *
     * ⚠️ لا يوجد overlap: المدير لن يُحوَّل لـ /app، والموظف لن يدخل /admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $panelId = $panel->getId();

        // super_admin يدخل /admin فقط — لا يُسمح له بالدخول لبوابة الموظفين
        if ($this->is_super_admin) {
            return $panelId === 'admin';
        }

        $isActive = $this->status === 'active';
        $level = (int) ($this->security_level ?? 1);

        return match ($panelId) {
            'admin' => $isActive && $level >= 4,
            'app'   => $isActive && $level < 4,
            default => false,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment Protection (STRICT — No $guarded)
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        // Identity
        'employee_id',
        'name_ar',
        'name_en',
        'email',
        'password',
        'phone',
        'national_id',
        'avatar',
        'gender',
        'date_of_birth',

        // Organizational
        'branch_id',
        'department_id',
        'role_id',
        'direct_manager_id',
        'job_title_ar',
        'job_title_en',
        'hire_date',
        'employment_type',
        'status',

        // Financial
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'working_days_per_month',
        'working_hours_per_day',

        // Security (NOT mass-assignable: is_super_admin, security_level, is_trap_target)
        // These are set explicitly via dedicated methods.

        // Gamification
        'total_points',
        'current_streak',
        'longest_streak',

        // Preferences
        'locale',
        'timezone',
    ];

    /**
     * Attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'is_trap_target',
        'risk_score',
        'national_id',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'date_of_birth'      => 'date',
            'hire_date'          => 'date',
            'last_login_at'      => 'datetime',
            'locked_until'       => 'datetime',
            'basic_salary'       => 'decimal:2',
            'housing_allowance'  => 'decimal:2',
            'transport_allowance'=> 'decimal:2',
            'other_allowances'   => 'decimal:2',
            'is_super_admin'     => 'boolean',
            'is_trap_target'     => 'boolean',
            'risk_score'         => 'integer',
            'total_points'       => 'integer',
            'current_streak'     => 'integer',
            'longest_streak'     => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL INTELLIGENCE — Salary-to-Minute Engine
    |--------------------------------------------------------------------------
    |
    | Formula:  cost_per_minute = basic_salary / working_days / hours / 60
    | Example:  8000 SAR / 22 days / 8 hours / 60 min = 0.7576 SAR/min
    |
    */

    /**
     * Get total monthly compensation.
     */
    public function getTotalSalaryAttribute(): float
    {
        return (float) $this->basic_salary
             + (float) $this->housing_allowance
             + (float) $this->transport_allowance
             + (float) $this->other_allowances;
    }

    /**
     * Get total working minutes per month.
     */
    public function getMonthlyWorkingMinutesAttribute(): int
    {
        return $this->working_days_per_month * $this->working_hours_per_day * 60;
    }

    /**
     * Calculate cost per minute based on BASIC salary only.
     * This is the financial loss rate for each minute of delay.
     */
    public function getCostPerMinuteAttribute(): float
    {
        $minutes = $this->monthly_working_minutes;

        if ($minutes <= 0) {
            return 0.0;
        }

        return round((float) $this->basic_salary / $minutes, 4);
    }

    /**
     * Calculate cost per minute based on TOTAL compensation.
     */
    public function getTotalCostPerMinuteAttribute(): float
    {
        $minutes = $this->monthly_working_minutes;

        if ($minutes <= 0) {
            return 0.0;
        }

        return round($this->total_salary / $minutes, 4);
    }

    /**
     * Calculate the financial cost of a given number of delay minutes.
     */
    public function calculateDelayCost(int $minutes): float
    {
        return round($minutes * $this->cost_per_minute, 2);
    }

    /**
     * Get the daily salary rate.
     */
    public function getDailyRateAttribute(): float
    {
        if ($this->working_days_per_month <= 0) {
            return 0.0;
        }

        return round((float) $this->basic_salary / $this->working_days_per_month, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    // --- Organizational ---

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'direct_manager_id');
    }

    // --- Attendance & Finance ---

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function financialReports(): HasMany
    {
        return $this->hasMany(FinancialReport::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // --- Messaging ---

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                     ->withPivot('is_muted', 'last_read_at')
                     ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function performanceAlerts(): HasMany
    {
        return $this->hasMany(PerformanceAlert::class);
    }

    // --- Gamification ---

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
                     ->withPivot('awarded_at', 'awarded_reason')
                     ->withTimestamps();
    }

    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class);
    }

    // --- Security ---

    public function trapInteractions(): HasMany
    {
        return $this->hasMany(TrapInteraction::class);
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'user_shifts')
                     ->withPivot('effective_from', 'effective_to', 'is_current')
                     ->withTimestamps();
    }

    public function currentShift(): ?Shift
    {
        return $this->shifts()
                     ->wherePivot('is_current', true)
                     ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | RBAC HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has a specific permission via their role.
     */
    public function hasPermission(string $slug): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->role
            && $this->role->permissions()->where('slug', $slug)->exists();
    }

    /**
     * Check if user's security level meets the minimum requirement.
     */
    public function hasSecurityLevel(int $minimumLevel): bool
    {
        return $this->security_level >= $minimumLevel;
    }

    /**
     * Check if the user can manage another user (higher level = more authority).
     */
    public function canManage(User $target): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->security_level > $target->security_level;
    }

    /*
    |--------------------------------------------------------------------------
    | SECURITY HELPERS (Not mass-assignable)
    |--------------------------------------------------------------------------
    */

    /**
     * Set security level explicitly (bypasses $fillable).
     */
    public function setSecurityLevel(int $level): self
    {
        $this->forceFill(['security_level' => max(1, min($level, 10))])->save();
        return $this;
    }

    /**
     * Promote to super admin (bypasses $fillable).
     */
    public function promoteToSuperAdmin(): self
    {
        $this->forceFill([
            'is_super_admin' => true,
            'security_level' => 10,
        ])->save();
        return $this;
    }

    /**
     * Mark as trap target for integrity testing.
     */
    public function enableTrapMonitoring(): self
    {
        $this->forceFill(['is_trap_target' => true])->save();
        return $this;
    }

    /**
     * Calculate and persist logarithmic risk score after a trap interaction.
     *
     * Formula: risk_score = 10 × (2^n − 1)
     * Where n = total number of trap interactions for this user.
     *
     * Progression: 10 → 30 → 70 → 150 → 310 → 630 ...
     * Each subsequent trigger costs MORE than the sum of all previous triggers.
     *
     * Uses forceFill() because risk_score is NOT in $fillable.
     */
    public function incrementRiskScore(): int
    {
        $n = $this->trapInteractions()->count();
        $newScore = (int) (10 * (pow(2, $n) - 1));

        $this->forceFill(['risk_score' => $newScore])->save();

        return $newScore;
    }

    /**
     * Human-readable risk category based on cumulative score.
     *
     * score < 30  → low
     * score < 100 → medium
     * score < 300 → high
     * score >= 300 → critical
     */
    public function getRiskLevelAttribute(): string
    {
        $score = $this->risk_score ?? 0;

        return match (true) {
            $score < 30  => 'low',
            $score < 100 => 'medium',
            $score < 300 => 'high',
            default      => 'critical',
        };
    }

    /**
     * Record a successful login.
     */
    public function recordLogin(string $ip): void
    {
        $this->forceFill([
            'last_login_at'        => now(),
            'last_login_ip'        => $ip,
            'failed_login_attempts'=> 0,
            'locked_until'         => null,
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeWithSecurityLevel($query, int $minLevel)
    {
        return $query->where('security_level', '>=', $minLevel);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the localized name based on current app locale.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Get the localized job title.
     */
    public function getJobTitleAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->job_title_ar : $this->job_title_en;
    }

    /**
     * Generate a unique employee ID.
     */
    public static function generateEmployeeId(): string
    {
        $prefix = 'SARH';
        $year = now()->format('y');
        $sequence = str_pad(static::withTrashed()->count() + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }

    /**
     * Boot method — auto-generate employee_id on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->employee_id)) {
                $user->employee_id = static::generateEmployeeId();
            }
        });
    }
}
