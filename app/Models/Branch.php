<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'address_ar',
        'address_en',
        'city_ar',
        'city_en',
        'phone',
        'email',
        'latitude',
        'longitude',
        'geofence_radius',
        'default_shift_start',
        'default_shift_end',
        'grace_period_minutes',
        'is_active',
        'monthly_salary_budget',
        'monthly_delay_losses',
        'cost_center_code',
        'annual_budget',
        'target_attendance_rate',
        'max_acceptable_loss_percent',
        'vpm_target',
        'weekend_days',    // JSON: ['friday','saturday'] — أيام العطلة الأسبوعية
    ];

    protected function casts(): array
    {
        return [
            'latitude'             => 'decimal:7',
            'longitude'            => 'decimal:7',
            'geofence_radius'      => 'integer',
            'grace_period_minutes' => 'integer',
            'is_active'            => 'boolean',
            'monthly_salary_budget'=> 'decimal:2',
            'monthly_delay_losses' => 'decimal:2',
            'annual_budget'              => 'decimal:2',
            'target_attendance_rate'     => 'decimal:2',
            'max_acceptable_loss_percent'=> 'decimal:2',
            'vpm_target'                 => 'decimal:2',
            'weekend_days'               => 'array', // JSON cast
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function financialReports(): HasMany
    {
        return $this->hasMany(FinancialReport::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function analyticsSnapshots(): HasMany
    {
        return $this->hasMany(AnalyticsSnapshot::class);
    }

    public function lossAlerts(): HasMany
    {
        return $this->hasMany(LossAlert::class);
    }

    /*
    |--------------------------------------------------------------------------
    | GEOFENCING LOGIC (Haversine Formula)
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate distance in meters between this branch and given coordinates.
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad((float) $this->latitude);
        $lonFrom = deg2rad((float) $this->longitude);
        $latTo   = deg2rad($lat);
        $lonTo   = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
           + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Check if given coordinates are within the geofence.
     */
    public function isWithinGeofence(float $lat, float $lng): bool
    {
        return $this->distanceTo($lat, $lng) <= $this->geofence_radius;
    }

    /*
    |--------------------------------------------------------------------------
    | FINANCIAL HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Recalculate cached monthly salary budget from active employees.
     */
    public function recalculateSalaryBudget(): void
    {
        $this->update([
            'monthly_salary_budget' => $this->users()->active()->sum('basic_salary'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES & ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
