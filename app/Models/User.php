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

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    /**
     * شارات الموظف مع بيانات الشارة الأصلية.
     */
    public function awardedBadges(): HasMany
    {
        return $this->badges()->with('badge');
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

    // --- RBAC Overrides (Module 2: Granular RBAC) ---

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                     ->withPivot('type', 'granted_by', 'expires_at', 'reason')
                     ->withTimestamps();
    }

    // --- Attendance Exceptions (Module 7) ---

    public function attendanceExceptions(): HasMany
    {
        return $this->hasMany(AttendanceException::class);
    }

    /**
     * Get active attendance exception for today.
     */
    public function getActiveException(): ?AttendanceException
    {
        return AttendanceException::getActiveForUser($this->id);
    }

    // --- Score Adjustments (Module 8) ---

    public function scoreAdjustments(): HasMany
    {
        return $this->hasMany(ScoreAdjustment::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(UserShift::class);
    }

    /**
     * تعيين الشفت النشط الحالي.
     */
    public function activeShift(): ?UserShift
    {
        return $this->shifts()->active()->current()->first();
    }

    /**
     * الشفت الحالي — يرجع كائن Shift مباشرة (للتوافق مع AttendanceService).
     */
    public function currentShift(): ?Shift
    {
        return $this->activeShift()?->shift;
    }

    /**
     * تاريخ الشفتات مرتب من الأحدث.
     */
    public function shiftHistory(): HasMany
    {
        return $this->shifts()->orderBy('effective_from', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | RBAC HELPERS — صلاحيات فردية بحتة (v4.1)
    |--------------------------------------------------------------------------
    |
    | المصدر الوحيد للصلاحيات: جدول user_permissions
    | الأدوار فخرية فقط — لا تؤثر على الصلاحيات
    |
    | الأولوية:
    |   1. super_admin / security_level 10 → true دائمًا
    |   2. revoke نشط → false (حظر صريح)
    |   3. grant نشط → true (تصريح صريح)
    |   4. تبعيات ذكية — صلاحية تمنح أخرى ضمنيًا
    |   5. القيمة الافتراضية → false (ممنوع)
    |
    */

    /**
     * خريطة التبعيات الذكية: صلاحية → صلاحيات ضمنية.
     * مثلاً: إذا عندك create_user تلقائيًا تقدر تشوف المستخدمين.
     * المصفوفة مسطّحة بالكامل (لا حاجة لتتبع متعدي).
     */
    protected static array $permissionImplies = [
        // ── Attendance hierarchy ──
        'attendance.view_all'     => ['attendance.view_branch', 'attendance.view_team', 'attendance.view_own'],
        'attendance.view_branch'  => ['attendance.view_team', 'attendance.view_own'],
        'attendance.view_team'    => ['attendance.view_own'],
        'attendance.manual_entry' => ['attendance.view_own'],
        'attendance.approve'      => ['attendance.view_team', 'attendance.view_own'],
        'attendance.export'       => ['attendance.view_own'],

        // ── Finance hierarchy ──
        'finance.view_all'          => ['finance.view_branch', 'finance.view_team', 'finance.view_own'],
        'finance.view_branch'       => ['finance.view_team', 'finance.view_own'],
        'finance.view_team'         => ['finance.view_own'],
        'finance.manage_salaries'   => ['finance.view_all', 'finance.view_branch', 'finance.view_team', 'finance.view_own', 'finance.dashboard'],
        'finance.generate_reports'  => ['finance.view_own', 'finance.dashboard'],
        'finance.dashboard'         => ['finance.view_own'],

        // ── Users CRUD ──
        'users.create'       => ['users.view'],
        'users.edit'         => ['users.view'],
        'users.delete'       => ['users.view'],
        'users.manage_roles' => ['users.view'],

        // ── Branches CRUD ──
        'branches.create' => ['branches.view'],
        'branches.edit'   => ['branches.view'],
        'branches.delete' => ['branches.view'],

        // ── Leaves ──
        'leaves.approve'  => ['leaves.view_all', 'leaves.request'],
        'leaves.view_all' => ['leaves.request'],

        // ── Whistleblower ──
        'whistleblower.investigate' => ['whistleblower.view', 'whistleblower.submit'],
        'whistleblower.view'        => ['whistleblower.submit'],

        // ── Traps ──
        'traps.manage' => ['traps.view'],

        // ── Messaging ──
        'messaging.broadcast' => ['messaging.chat'],
        'messaging.circulars' => ['messaging.chat'],

        // ── Gamification ──
        'gamification.manage'   => ['gamification.view_all', 'gamification.view_own'],
        'gamification.view_all' => ['gamification.view_own'],

        // ── System ──
        'system.settings' => ['system.audit_logs', 'system.manage_holidays'],
    ];

    /**
     * كاش مؤقت لصلاحيات الطلب الحالي (تفادي N+1).
     */
    private ?\Illuminate\Support\Collection $cachedActivePermissions = null;

    /**
     * تحميل كل صلاحيات المستخدم النشطة (مرة واحدة في الطلب).
     */
    private function loadActivePermissions(): \Illuminate\Support\Collection
    {
        if ($this->cachedActivePermissions === null) {
            $this->cachedActivePermissions = $this->userPermissions()
                ->active()
                ->with('permission')
                ->get();
        }

        return $this->cachedActivePermissions;
    }

    /**
     * مسح الكاش (يُستدعى بعد تعديل الصلاحيات).
     */
    public function flushPermissionCache(): void
    {
        $this->cachedActivePermissions = null;
    }

    /**
     * التحقق من صلاحية محددة — المصدر الوحيد: UserPermission.
     */
    public function hasPermission(string $slug): bool
    {
        // 1. المدير العام يتجاوز كل شيء
        if ($this->is_super_admin || $this->security_level === 10) {
            return true;
        }

        $activePermissions = $this->loadActivePermissions();

        // 2. حظر صريح (revoke) يتجاوز كل شيء
        $isRevoked = $activePermissions
            ->where('type', 'revoke')
            ->contains(fn ($up) => $up->permission && $up->permission->slug === $slug);

        if ($isRevoked) {
            return false;
        }

        // 3. تصريح صريح (grant)
        $isGranted = $activePermissions
            ->where('type', 'grant')
            ->contains(fn ($up) => $up->permission && $up->permission->slug === $slug);

        if ($isGranted) {
            return true;
        }

        // 4. تبعيات ذكية: هل صلاحية ممنوحة تتضمن هذه الصلاحية ضمنيًا؟
        return $this->isImpliedByGrantedPermissions($slug, $activePermissions);
    }

    /**
     * تحقق إذا كانت صلاحية مطلوبة مضمّنة ضمنيًا في صلاحية ممنوحة.
     */
    private function isImpliedByGrantedPermissions(string $slug, \Illuminate\Support\Collection $activePermissions): bool
    {
        $grantedSlugs = $activePermissions
            ->where('type', 'grant')
            ->pluck('permission.slug')
            ->filter()
            ->toArray();

        foreach ($grantedSlugs as $grantedSlug) {
            $implied = static::$permissionImplies[$grantedSlug] ?? [];
            if (in_array($slug, $implied, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * هل عند المستخدم أي من هذه الصلاحيات؟
     */
    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * كل الصلاحيات الفعلية (للعرض في الواجهة).
     */
    public function getEffectivePermissions(): \Illuminate\Support\Collection
    {
        if ($this->is_super_admin || $this->security_level === 10) {
            return Permission::all();
        }

        $activePermissions = $this->loadActivePermissions();

        // الصلاحيات الممنوحة صراحة
        $grantedIds = $activePermissions
            ->where('type', 'grant')
            ->pluck('permission_id')
            ->unique();

        // الصلاحيات المسحوبة صراحة
        $revokedIds = $activePermissions
            ->where('type', 'revoke')
            ->pluck('permission_id')
            ->unique();

        // الصلاحيات الضمنية من خريطة التبعيات
        $grantedSlugs = $activePermissions
            ->where('type', 'grant')
            ->pluck('permission.slug')
            ->filter()
            ->toArray();

        $impliedSlugs = [];
        foreach ($grantedSlugs as $grantedSlug) {
            $implied = static::$permissionImplies[$grantedSlug] ?? [];
            $impliedSlugs = array_merge($impliedSlugs, $implied);
        }
        $impliedSlugs = array_unique($impliedSlugs);

        $impliedIds = !empty($impliedSlugs)
            ? Permission::whereIn('slug', $impliedSlugs)->pluck('id')
            : collect();

        // الفعلية = (الممنوحة + الضمنية) - المسحوبة
        $effectiveIds = $grantedIds
            ->merge($impliedIds)
            ->diff($revokedIds)
            ->unique()
            ->values();

        return Permission::whereIn('id', $effectiveIds)->get();
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
