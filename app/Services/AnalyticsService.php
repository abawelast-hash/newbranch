<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\EmployeePattern;
use App\Models\Holiday;
use App\Models\LossAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /*
    |--------------------------------------------------------------------------
    | 1. VALUE PER MINUTE (VPM) — Core Metric
    |--------------------------------------------------------------------------
    | VPM = Total Branch Salary Budget / Total Available Minutes
    | Available Minutes = Active Employees × Working Hours × Working Days
    */

    public function calculateVPM(Branch $branch, ?string $period = null): float
    {
        $period = $period ?? now()->format('Y-m');
        $year   = (int) substr($period, 0, 4);
        $month  = (int) substr($period, 5, 2);

        $totalBudget   = (float) ($branch->monthly_salary_budget ?? 0);
        $employeeCount = $branch->users()->where('status', 'active')->count();

        if ($employeeCount === 0 || $totalBudget === 0.0) {
            return 0;
        }

        // Calculate working days (exclude weekends + holidays)
        $startDate  = Carbon::create($year, $month, 1);
        $endDate    = $startDate->copy()->endOfMonth();
        $workingDays = $this->countWorkingDays($startDate, $endDate, $branch->id);

        $avgHoursPerDay = 8; // Default
        if ($branch->default_shift_start && $branch->default_shift_end) {
            $s = Carbon::parse($branch->default_shift_start);
            $e = Carbon::parse($branch->default_shift_end);
            if ($e->lt($s)) $e->addDay();
            $avgHoursPerDay = $s->diffInMinutes($e) / 60;
        }

        $totalMinutes = $employeeCount * $workingDays * $avgHoursPerDay * 60;

        return $totalMinutes > 0 ? round($totalBudget / $totalMinutes, 4) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. TOTAL LOSS ENGINE
    |--------------------------------------------------------------------------
    | Aggregates ALL types of financial losses:
    | - Delay losses (late arrivals)
    | - Absence losses (no-shows)
    | - Early leave losses
    | Each minute has a VPM cost → total = ∑(lost_minutes × VPM)
    */

    public function calculateTotalLoss(Branch $branch, Carbon $date): array
    {
        // Skip weekends (Friday=5, Saturday=6) and holidays — no loss on non-working days
        if (in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
            return [
                'delay_losses'        => 0,
                'absence_losses'      => 0,
                'early_leave_losses'  => 0,
                'total_losses'        => 0,
                'absent_count'        => 0,
                'present_count'       => 0,
                'late_count'          => 0,
                'total_delay_minutes' => 0,
            ];
        }

        // Skip holidays
        $isHoliday = Holiday::where('date', $date->toDateString())
            ->where(function ($q) use ($branch) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->exists();

        if ($isHoliday) {
            return [
                'delay_losses'        => 0,
                'absence_losses'      => 0,
                'early_leave_losses'  => 0,
                'total_losses'        => 0,
                'absent_count'        => 0,
                'present_count'       => 0,
                'late_count'          => 0,
                'total_delay_minutes' => 0,
            ];
        }

        $logs = AttendanceLog::where('branch_id', $branch->id)
            ->whereDate('attendance_date', $date)
            ->get();

        $delayLoss     = (float) $logs->sum('delay_cost');
        $earlyLeaveLoss = (float) $logs->sum('early_leave_cost');

        // Absence loss: employees who didn't check in at all
        $activeEmployees = $branch->users()->where('status', 'active')->count();
        $presentIds      = $logs->pluck('user_id')->unique()->count();
        $absentCount     = max(0, $activeEmployees - $presentIds);

        $vpm = $this->calculateVPM($branch);
        $shiftMinutes = 480;
        if ($branch->default_shift_start && $branch->default_shift_end) {
            $s = Carbon::parse($branch->default_shift_start);
            $e = Carbon::parse($branch->default_shift_end);
            if ($e->lt($s)) $e->addDay();
            $shiftMinutes = (int) $s->diffInMinutes($e);
        }

        $absenceLoss = round($absentCount * $shiftMinutes * $vpm, 2);
        $totalLoss   = $delayLoss + $earlyLeaveLoss + $absenceLoss;

        return [
            'delay_losses'       => $delayLoss,
            'absence_losses'     => $absenceLoss,
            'early_leave_losses' => $earlyLeaveLoss,
            'total_losses'       => $totalLoss,
            'absent_count'       => $absentCount,
            'present_count'      => $presentIds,
            'late_count'         => $logs->where('status', 'late')->count(),
            'total_delay_minutes' => (int) $logs->sum('delay_minutes'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 3. PRODUCTIVITY GAP
    |--------------------------------------------------------------------------
    | Gap = ((Target Attendance % - Actual Attendance %) / Target) × 100
    */

    public function calculateProductivityGap(Branch $branch, Carbon $date): float
    {
        $target = (float) ($branch->target_attendance_rate ?? 95.0);
        $activeCount = $branch->users()->where('status', 'active')->count();

        if ($activeCount === 0) return 0;

        $presentCount = AttendanceLog::where('branch_id', $branch->id)
            ->whereDate('attendance_date', $date)
            ->distinct('user_id')
            ->count('user_id');

        $actualRate = ($presentCount / $activeCount) * 100;

        return $target > 0 ? round((($target - $actualRate) / $target) * 100, 2) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | 4. EFFICIENCY SCORE (0-100)
    |--------------------------------------------------------------------------
    | Composite score:
    | - 40% Attendance Rate
    | - 30% Punctuality (inverse of avg delay)
    | - 20% Retention (worked minutes vs expected)
    | - 10% Zero-loss days streak
    */

    public function calculateEfficiencyScore(Branch $branch, Carbon $startDate, Carbon $endDate): float
    {
        $activeCount = $branch->users()->where('status', 'active')->count();
        if ($activeCount === 0) return 0;

        $logs = AttendanceLog::where('branch_id', $branch->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $workingDays = $this->countWorkingDays($startDate, $endDate, $branch->id);
        $expectedAttendances = $activeCount * max($workingDays, 1);

        // Attendance Rate (40%)
        $uniqueDayUsers = $logs->groupBy(fn ($l) => $l->attendance_date . '_' . $l->user_id)->count();
        $attendanceRate = min(100, ($uniqueDayUsers / max($expectedAttendances, 1)) * 100);
        $attendanceScore = $attendanceRate * 0.4;

        // Punctuality (30%) — inverse of average delay
        $avgDelay = $logs->avg('delay_minutes') ?? 0;
        $punctualityScore = max(0, min(100, 100 - ($avgDelay * 3.33))) * 0.3; // 30 min delay = 0

        // Retention (20%) — worked vs expected minutes
        $totalWorked = (float) $logs->sum('worked_minutes');
        $shiftMinutes = 480;
        if ($branch->default_shift_start && $branch->default_shift_end) {
            $s = Carbon::parse($branch->default_shift_start);
            $e = Carbon::parse($branch->default_shift_end);
            if ($e->lt($s)) $e->addDay();
            $shiftMinutes = (int) $s->diffInMinutes($e);
        }
        $expectedMinutes = $expectedAttendances * $shiftMinutes;
        $retentionRate = $expectedMinutes > 0 ? min(100, ($totalWorked / $expectedMinutes) * 100) : 0;
        $retentionScore = $retentionRate * 0.2;

        // Zero-loss streak (10%)
        $zeroLossDays = $logs->groupBy('attendance_date')
            ->filter(fn ($dayLogs) => $dayLogs->sum('delay_cost') == 0 && $dayLogs->sum('early_leave_cost') == 0)
            ->count();
        $streakScore = min(100, ($zeroLossDays / max($workingDays, 1)) * 100) * 0.1;

        return round($attendanceScore + $punctualityScore + $retentionScore + $streakScore, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. ROI vs DISCIPLINE MATRIX
    |--------------------------------------------------------------------------
    | For each branch: plot (Investment = Salary Budget, Return = Efficiency Score)
    | Quadrant analysis: High ROI / Low ROI × High Discipline / Low Discipline
    */

    public function calculateROIMatrix(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate   = $endDate ?? now();

        $branches = Branch::where('is_active', true)->get();
        $matrix   = [];

        foreach ($branches as $branch) {
            $efficiency = $this->calculateEfficiencyScore($branch, $startDate, $endDate);
            $budget     = (float) ($branch->monthly_salary_budget ?? 0);
            $losses     = $this->calculateTotalLoss($branch, now())['total_losses'];

            $roi = $budget > 0 ? round((($budget - $losses) / $budget) * 100, 2) : 0;

            $matrix[] = [
                'branch_id'   => $branch->id,
                'branch_name' => $branch->name_ar ?? $branch->name,
                'budget'      => $budget,
                'losses'      => $losses,
                'roi'         => $roi,
                'efficiency'  => $efficiency,
                'quadrant'    => $this->determineQuadrant($roi, $efficiency),
            ];
        }

        return $matrix;
    }

    private function determineQuadrant(float $roi, float $efficiency): string
    {
        $roiThreshold = 90;
        $effThreshold = 70;

        return match (true) {
            $roi >= $roiThreshold && $efficiency >= $effThreshold => 'star',       // ⭐ High ROI + High Discipline
            $roi >= $roiThreshold && $efficiency < $effThreshold  => 'cash_cow',   // 💰 High ROI + Low Discipline
            $roi < $roiThreshold && $efficiency >= $effThreshold  => 'potential',   // 🚀 Low ROI + High Discipline
            default                                                => 'at_risk',    // ⚠️ Low ROI + Low Discipline
        };
    }

    /*
    |--------------------------------------------------------------------------
    | 6. ATTENDANCE HEATMAP
    |--------------------------------------------------------------------------
    | Returns hourly distribution of check-ins for a branch over a date range
    */

    public function generateHeatmapData(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        $logs = AttendanceLog::where('branch_id', $branch->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereNotNull('check_in_at')
            ->get();

        // Hours distribution (6:00 - 23:00)
        $hourly = array_fill(6, 18, 0);
        foreach ($logs as $log) {
            $hour = (int) Carbon::parse($log->check_in_at)->format('H');
            if (isset($hourly[$hour])) {
                $hourly[$hour]++;
            }
        }

        // Day-of-week distribution (0=Sun to 6=Sat)
        $daily = array_fill(0, 7, ['present' => 0, 'late' => 0, 'absent' => 0]);
        foreach ($logs as $log) {
            $dow = Carbon::parse($log->attendance_date)->dayOfWeek;
            if ($log->status === 'late') {
                $daily[$dow]['late']++;
            } else {
                $daily[$dow]['present']++;
            }
        }

        return [
            'hourly_distribution' => $hourly,
            'daily_distribution'  => $daily,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 7. PREDICTIVE ANALYTICS — Pattern Detection
    |--------------------------------------------------------------------------
    */

    /**
     * Detect frequent late-comers (employees late > 3 times in last 2 weeks)
     */
    public function detectFrequentLatePattern(Branch $branch): Collection
    {
        $twoWeeksAgo = now()->subDays(14);

        $lateCounts = AttendanceLog::where('branch_id', $branch->id)
            ->where('attendance_date', '>=', $twoWeeksAgo)
            ->where('status', 'late')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as late_count'), DB::raw('SUM(delay_minutes) as total_delay'))
            ->having('late_count', '>=', 3)
            ->get();

        $patterns = collect();
        foreach ($lateCounts as $record) {
            $user = User::find($record->user_id);
            if (!$user) continue;

            $score = min(100, ($record->late_count / 10) * 100);
            $impact = (float) $record->total_delay * ($user->cost_per_minute ?? 0);

            $pattern = EmployeePattern::updateOrCreate(
                [
                    'user_id'      => $record->user_id,
                    'pattern_type' => 'frequent_late',
                    'is_active'    => true,
                ],
                [
                    'branch_id'        => $branch->id,
                    'frequency_score'  => $score,
                    'financial_impact' => $impact,
                    'pattern_data'     => [
                        'late_count'    => $record->late_count,
                        'total_delay'   => $record->total_delay,
                        'period_days'   => 14,
                    ],
                    'description_ar'   => "تأخير متكرر: {$record->late_count} مرات في آخر أسبوعين ({$record->total_delay} دقيقة إجمالي)",
                    'description_en'   => "Frequent late: {$record->late_count} times in last 2 weeks ({$record->total_delay} min total)",
                    'risk_level'       => $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low'),
                    'detected_at'      => now()->toDateString(),
                    'valid_until'      => now()->addDays(14)->toDateString(),
                ]
            );

            $patterns->push($pattern);
        }

        return $patterns;
    }

    /**
     * Detect pre-holiday absence patterns (employees who are often absent before/after holidays)
     */
    public function detectPreHolidayPattern(Branch $branch): Collection
    {
        $patterns = collect();
        $holidays = Holiday::where(function ($q) use ($branch) {
            $q->whereNull('branch_id')->orWhere('branch_id', $branch->id);
        })->where('date', '>=', now()->subMonths(3))->get();

        if ($holidays->isEmpty()) return $patterns;

        $employees = $branch->users()->where('status', 'active')->get();

        foreach ($employees as $user) {
            $preHolidayAbsences = 0;
            $totalHolidays = $holidays->count();

            foreach ($holidays as $holiday) {
                $dayBefore = Carbon::parse($holiday->date)->subDay();
                $dayAfter  = Carbon::parse($holiday->date)->addDay();

                $absentBefore = AttendanceLog::where('user_id', $user->id)
                    ->whereDate('attendance_date', $dayBefore)
                    ->where('status', 'absent')
                    ->exists();

                $absentAfter = AttendanceLog::where('user_id', $user->id)
                    ->whereDate('attendance_date', $dayAfter)
                    ->where('status', 'absent')
                    ->exists();

                // Also check if no log at all (no-show)
                $noShowBefore = !AttendanceLog::where('user_id', $user->id)
                    ->whereDate('attendance_date', $dayBefore)
                    ->exists();

                $noShowAfter = !AttendanceLog::where('user_id', $user->id)
                    ->whereDate('attendance_date', $dayAfter)
                    ->exists();

                if ($absentBefore || $noShowBefore) $preHolidayAbsences++;
                if ($absentAfter || $noShowAfter) $preHolidayAbsences++;
            }

            $rate = $totalHolidays > 0 ? ($preHolidayAbsences / ($totalHolidays * 2)) * 100 : 0;

            if ($rate >= 50) {
                $costPerDay = ($user->basic_salary ?? 0) / max($user->working_days_per_month ?? 22, 1);
                $impact     = $preHolidayAbsences * $costPerDay;

                $pattern = EmployeePattern::updateOrCreate(
                    [
                        'user_id'      => $user->id,
                        'pattern_type' => 'pre_holiday_absence',
                        'is_active'    => true,
                    ],
                    [
                        'branch_id'        => $branch->id,
                        'frequency_score'  => round($rate, 2),
                        'financial_impact' => round($impact, 2),
                        'pattern_data'     => [
                            'absences'        => $preHolidayAbsences,
                            'total_holidays'  => $totalHolidays,
                            'rate'            => round($rate, 2),
                        ],
                        'description_ar'   => "نمط غياب قبل/بعد الإجازات: {$preHolidayAbsences} مرات من أصل " . ($totalHolidays * 2) . " فرصة",
                        'description_en'   => "Pre/post holiday absence: {$preHolidayAbsences} out of " . ($totalHolidays * 2) . " opportunities",
                        'risk_level'       => $rate >= 80 ? 'critical' : ($rate >= 60 ? 'high' : 'medium'),
                        'detected_at'      => now()->toDateString(),
                        'valid_until'      => now()->addDays(30)->toDateString(),
                    ]
                );

                $patterns->push($pattern);
            }
        }

        return $patterns;
    }

    /**
     * Detect monthly cycle patterns (e.g., always late on Sundays or first week of month)
     */
    public function detectMonthlyCyclePattern(Branch $branch): Collection
    {
        $patterns   = collect();
        $threeMonthsAgo = now()->subMonths(3);
        $employees  = $branch->users()->where('status', 'active')->get();

        foreach ($employees as $user) {
            $logs = AttendanceLog::where('user_id', $user->id)
                ->where('attendance_date', '>=', $threeMonthsAgo)
                ->get();

            if ($logs->count() < 20) continue; // Need enough data

            // Check day-of-week patterns
            $dayOfWeekLate = [];
            foreach ($logs as $log) {
                $dow = Carbon::parse($log->attendance_date)->dayOfWeek;
                if (!isset($dayOfWeekLate[$dow])) {
                    $dayOfWeekLate[$dow] = ['late' => 0, 'total' => 0];
                }
                $dayOfWeekLate[$dow]['total']++;
                if ($log->status === 'late') {
                    $dayOfWeekLate[$dow]['late']++;
                }
            }

            // Find days with > 60% late rate
            $problematicDays = [];
            foreach ($dayOfWeekLate as $dow => $counts) {
                if ($counts['total'] >= 4) { // At least 4 occurrences
                    $lateRate = ($counts['late'] / $counts['total']) * 100;
                    if ($lateRate >= 60) {
                        $dayNames = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                        $problematicDays[] = [
                            'day'  => $dayNames[$dow] ?? $dow,
                            'rate' => round($lateRate, 1),
                        ];
                    }
                }
            }

            if (!empty($problematicDays)) {
                $daysList = implode('، ', array_column($problematicDays, 'day'));
                $avgRate  = collect($problematicDays)->avg('rate');

                $pattern = EmployeePattern::updateOrCreate(
                    [
                        'user_id'      => $user->id,
                        'pattern_type' => 'monthly_cycle',
                        'is_active'    => true,
                    ],
                    [
                        'branch_id'        => $branch->id,
                        'frequency_score'  => round($avgRate, 2),
                        'financial_impact' => round($logs->where('status', 'late')->sum('delay_cost'), 2),
                        'pattern_data'     => [
                            'problematic_days' => $problematicDays,
                            'analysis_period'  => '3_months',
                        ],
                        'description_ar'   => "نمط تأخير دوري: يتأخر بشكل متكرر أيام {$daysList}",
                        'description_en'   => "Weekly cycle pattern: frequently late on specific days",
                        'risk_level'       => $avgRate >= 80 ? 'high' : 'medium',
                        'detected_at'      => now()->toDateString(),
                        'valid_until'      => now()->addDays(30)->toDateString(),
                    ]
                );

                $patterns->push($pattern);
            }
        }

        return $patterns;
    }

    /*
    |--------------------------------------------------------------------------
    | 8. SMART NOTIFICATION TRIGGERS
    |--------------------------------------------------------------------------
    | Auto-generate LossAlerts when thresholds are exceeded
    */

    public function checkAndTriggerAlerts(Branch $branch, Carbon $date): array
    {
        $alerts = [];
        $lossData = $this->calculateTotalLoss($branch, $date);
        $budget   = (float) ($branch->monthly_salary_budget ?? 0);
        $maxLoss  = (float) ($branch->max_acceptable_loss_percent ?? 5.0);

        // Daily loss threshold check
        $dailyBudget   = $budget / 22; // Approximate working days
        $dailyLossRate = $dailyBudget > 0 ? ($lossData['total_losses'] / $dailyBudget) * 100 : 0;

        if ($dailyLossRate > $maxLoss) {
            $alert = LossAlert::create([
                'branch_id'      => $branch->id,
                'alert_date'     => $date->toDateString(),
                'alert_type'     => 'threshold_exceeded',
                'severity'       => $dailyLossRate > ($maxLoss * 2) ? 'critical' : 'high',
                'threshold_value' => $maxLoss,
                'actual_value'   => round($dailyLossRate, 2),
                'description_ar' => "تجاوز حد الخسائر اليومية: {$dailyLossRate}% (الحد: {$maxLoss}%)",
                'description_en' => "Daily loss threshold exceeded: {$dailyLossRate}% (max: {$maxLoss}%)",
                'context_data'   => $lossData,
            ]);
            $alerts[] = $alert;
        }

        // Attendance rate alert
        $activeCount  = $branch->users()->where('status', 'active')->count();
        $target       = (float) ($branch->target_attendance_rate ?? 95.0);
        $actualRate   = $activeCount > 0 ? (($lossData['present_count'] / $activeCount) * 100) : 0;

        if ($actualRate < ($target - 10)) { // 10% below target
            $alert = LossAlert::create([
                'branch_id'      => $branch->id,
                'alert_date'     => $date->toDateString(),
                'alert_type'     => 'low_attendance',
                'severity'       => $actualRate < ($target - 20) ? 'critical' : 'high',
                'threshold_value' => $target,
                'actual_value'   => round($actualRate, 2),
                'description_ar' => "انخفاض حاد في الحضور: {$actualRate}% (المستهدف: {$target}%)",
                'description_en' => "Low attendance rate: {$actualRate}% (target: {$target}%)",
                'context_data'   => [
                    'active_employees' => $activeCount,
                    'present'          => $lossData['present_count'],
                    'absent'           => $lossData['absent_count'],
                ],
            ]);
            $alerts[] = $alert;
        }

        // Mass late alert (>30% of employees late)
        if ($activeCount > 0 && ($lossData['late_count'] / $activeCount) > 0.3) {
            $latePercent = round(($lossData['late_count'] / $activeCount) * 100, 1);
            $alert = LossAlert::create([
                'branch_id'      => $branch->id,
                'alert_date'     => $date->toDateString(),
                'alert_type'     => 'mass_late',
                'severity'       => 'high',
                'threshold_value' => 30,
                'actual_value'   => $latePercent,
                'description_ar' => "تأخير جماعي: {$latePercent}% من الموظفين متأخرون",
                'description_en' => "Mass late arrival: {$latePercent}% of employees are late",
                'context_data'   => [
                    'late_count'  => $lossData['late_count'],
                    'total'       => $activeCount,
                    'total_delay' => $lossData['total_delay_minutes'],
                ],
            ]);
            $alerts[] = $alert;
        }

        return $alerts;
    }

    /*
    |--------------------------------------------------------------------------
    | 9. DAILY SNAPSHOT GENERATOR
    |--------------------------------------------------------------------------
    | Called by Scheduler or Console Command to persist analytics
    */

    public function generateDailySnapshot(Branch $branch, ?Carbon $date = null): AnalyticsSnapshot
    {
        $date = $date ?? now();

        $lossData   = $this->calculateTotalLoss($branch, $date);
        $efficiency = $this->calculateEfficiencyScore($branch, $date->copy()->startOfMonth(), $date);
        $vpm        = $this->calculateVPM($branch, $date->format('Y-m'));
        $gap        = $this->calculateProductivityGap($branch, $date);
        $heatmap    = $this->generateHeatmapData($branch, $date, $date);

        $activeCount = $branch->users()->where('status', 'active')->count();
        $totalSalary = (float) ($branch->monthly_salary_budget ?? 0);
        $dailySalary = $totalSalary / 22;

        $attendanceRate = $activeCount > 0
            ? round(($lossData['present_count'] / $activeCount) * 100, 2)
            : 0;

        $lossRatio = $dailySalary > 0
            ? round(($lossData['total_losses'] / $dailySalary) * 100, 2)
            : 0;

        $roi = $dailySalary > 0
            ? round(($dailySalary - $lossData['total_losses']) / $dailySalary * 100, 2)
            : 0;

        return AnalyticsSnapshot::updateOrCreate(
            [
                'branch_id'     => $branch->id,
                'snapshot_date' => $date->toDateString(),
                'period_type'   => 'daily',
            ],
            [
                'total_employees'             => $activeCount,
                'present_count'               => $lossData['present_count'],
                'absent_count'                => $lossData['absent_count'],
                'late_count'                  => $lossData['late_count'],
                'attendance_rate'             => $attendanceRate,
                'total_delay_minutes'         => $lossData['total_delay_minutes'],
                'avg_delay_minutes'           => $lossData['late_count'] > 0
                    ? round($lossData['total_delay_minutes'] / $lossData['late_count'], 2)
                    : 0,
                'total_salary_cost'           => $dailySalary,
                'delay_losses'                => $lossData['delay_losses'],
                'absence_losses'              => $lossData['absence_losses'],
                'early_leave_losses'          => $lossData['early_leave_losses'],
                'total_losses'                => $lossData['total_losses'],
                'overtime_cost'               => 0, // TODO: from logs
                'vpm'                         => $vpm,
                'productivity_gap'            => $gap,
                'loss_ratio'                  => $lossRatio,
                'efficiency_score'            => $efficiency,
                'roi_discipline'              => $roi,
                'hourly_checkin_distribution' => $heatmap['hourly_distribution'],
                'daily_pattern_data'          => $heatmap['daily_distribution'],
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 10. RUN ALL ANALYTICS (Master Method)
    |--------------------------------------------------------------------------
    */

    public function runFullAnalysis(?Carbon $date = null): array
    {
        $date     = $date ?? now();
        $branches = Branch::where('is_active', true)->get();
        $results  = [];

        foreach ($branches as $branch) {
            try {
                // Generate snapshot
                $snapshot = $this->generateDailySnapshot($branch, $date);

                // Check alerts
                $alerts = $this->checkAndTriggerAlerts($branch, $date);

                // Detect patterns (weekly — only run on Sundays)
                $patterns = [];
                if ($date->isSunday()) {
                    $patterns = array_merge(
                        $this->detectFrequentLatePattern($branch)->toArray(),
                        $this->detectPreHolidayPattern($branch)->toArray(),
                        $this->detectMonthlyCyclePattern($branch)->toArray()
                    );
                }

                $results[$branch->id] = [
                    'branch'   => $branch->name_ar,
                    'snapshot' => $snapshot->id,
                    'alerts'   => count($alerts),
                    'patterns' => count($patterns),
                    'status'   => 'success',
                ];
            } catch (\Throwable $e) {
                Log::error("Analytics failed for branch {$branch->id}: {$e->getMessage()}");
                $results[$branch->id] = [
                    'branch' => $branch->name_ar,
                    'status' => 'error',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONAL MIRROR — Employee Self-View
    |--------------------------------------------------------------------------
    */

    public function getPersonalMirror(User $user): array
    {
        $branch    = $user->branch;
        $startDate = now()->startOfMonth();
        $endDate   = now();

        // Personal attendance
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $presentDays = $logs->whereIn('status', ['present', 'late'])->count();
        $lateDays    = $logs->where('status', 'late')->count();
        $absentDays  = $logs->where('status', 'absent')->count();
        $totalDelay  = (int) $logs->sum('delay_minutes');
        $totalLoss   = (float) $logs->sum('delay_cost') + (float) $logs->sum('early_leave_cost');

        // Streak calculation
        $streak = 0;
        $recentLogs = AttendanceLog::where('user_id', $user->id)
            ->orderByDesc('attendance_date')
            ->limit(30)
            ->get();

        foreach ($recentLogs as $log) {
            if ($log->status === 'present' && $log->delay_minutes == 0) {
                $streak++;
            } else {
                break;
            }
        }

        // Branch ranking
        $branchRank = null;
        if ($branch) {
            $branchUsers = AttendanceLog::where('branch_id', $branch->id)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->groupBy('user_id')
                ->select('user_id', DB::raw('AVG(delay_minutes) as avg_delay'))
                ->orderBy('avg_delay')
                ->pluck('user_id')
                ->values();

            $position = $branchUsers->search($user->id);
            if ($position !== false) {
                $branchRank = [
                    'position'    => $position + 1,
                    'total'       => $branchUsers->count(),
                    'percentile'  => round((1 - ($position / max($branchUsers->count(), 1))) * 100),
                ];
            }
        }

        // Performance score (0-100)
        $workingDays = max($this->countWorkingDays($startDate, $endDate, $branch?->id), 1);
        $attendanceRate = min(100, ($presentDays / $workingDays) * 100);
        $punctualityRate = $presentDays > 0 ? max(0, 100 - ($totalDelay / $presentDays) * 5) : 0;
        $performanceScore = round(($attendanceRate * 0.6) + ($punctualityRate * 0.4));

        // Motivational message based on performance
        $message = match (true) {
            $performanceScore >= 95 => 'أداء استثنائي! أنت قدوة لزملائك 🌟',
            $performanceScore >= 85 => 'أداء ممتاز، استمر على هذا المستوى 💪',
            $performanceScore >= 70 => 'أداء جيد، لكن يمكنك التحسن أكثر 📈',
            $performanceScore >= 50 => 'يحتاج لتحسين — التأخير يكلفك مالياً ⚠️',
            default                 => 'أداء ضعيف — راجع التزامك قبل فوات الأوان 🔴',
        };

        return [
            'performance_score'  => $performanceScore,
            'present_days'       => $presentDays,
            'late_days'          => $lateDays,
            'absent_days'        => $absentDays,
            'total_delay'        => $totalDelay,
            'total_loss'         => $totalLoss,
            'streak'             => $streak,
            'branch_rank'        => $branchRank,
            'message'            => $message,
            'working_days'       => $workingDays,
            'attendance_rate'    => round($attendanceRate, 1),
            'punctuality_rate'   => round($punctualityRate, 1),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LOST OPPORTUNITY CLOCK (Real-time cumulative loss today)
    |--------------------------------------------------------------------------
    */

    public function getLostOpportunityClock(): array
    {
        $today = now()->toDateString();
        $branches = Branch::where('is_active', true)->get();

        $totalLoss    = 0;
        $totalDelay   = 0;
        $totalAbsent  = 0;
        $branchLosses = [];

        foreach ($branches as $branch) {
            $loss = $this->calculateTotalLoss($branch, now());
            $totalLoss   += $loss['total_losses'];
            $totalDelay  += $loss['total_delay_minutes'];
            $totalAbsent += $loss['absent_count'];

            $branchLosses[] = [
                'name'   => $branch->name_ar,
                'loss'   => $loss['total_losses'],
                'delays' => $loss['total_delay_minutes'],
            ];
        }

        // Sort by highest loss
        usort($branchLosses, fn ($a, $b) => $b['loss'] <=> $a['loss']);

        return [
            'total_loss_today'    => round($totalLoss, 2),
            'total_delay_minutes' => $totalDelay,
            'total_absent'        => $totalAbsent,
            'branch_breakdown'    => array_slice($branchLosses, 0, 5), // Top 5
            'timestamp'           => now()->toDateTimeString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function countWorkingDays(Carbon $startDate, Carbon $endDate, ?int $branchId = null): int
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Skip Friday & Saturday (Saudi weekend)
            if (!in_array($current->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
                // Skip holidays
                $isHoliday = Holiday::where('date', $current->toDateString())
                    ->where(function ($q) use ($branchId) {
                        $q->whereNull('branch_id');
                        if ($branchId) {
                            $q->orWhere('branch_id', $branchId);
                        }
                    })
                    ->exists();

                if (!$isHoliday) {
                    $count++;
                }
            }
            $current->addDay();
        }

        return $count;
    }
}
