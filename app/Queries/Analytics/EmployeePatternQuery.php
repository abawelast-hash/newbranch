<?php

namespace App\Queries\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EmployeePatternQuery
{
    /**
     * أنماط الموظف الفردي — تاريخ الحضور والأداء.
     */
    public function getPattern(int $userId, ?int $month = null, ?int $year = null): object
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;

        return Cache::remember(
            "analytics.employee.{$userId}.{$year}.{$month}",
            now()->addMinutes(15),
            fn () => DB::table('attendance_logs')
                ->select([
                    'user_id',
                    DB::raw('COUNT(*)                                                        AS total_days'),
                    DB::raw('SUM(CASE WHEN status = "on_time" THEN 1 ELSE 0 END)            AS on_time'),
                    DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END)               AS late'),
                    DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END)             AS absent'),
                    DB::raw('MAX(streak)                                                     AS best_streak'),
                    DB::raw('COALESCE(SUM(cost), 0)                                         AS total_cost'),
                    DB::raw('AVG(NULLIF(late_minutes, 0))                                   AS avg_late_minutes'),
                    DB::raw('MAX(check_in)                                                   AS last_checkin'),
                ])
                ->where('user_id', $userId)
                ->whereYear('date',  $year)
                ->whereMonth('date', $month)
                ->groupBy('user_id')
                ->first() ?? (object)[]
        );
    }

    public function invalidate(int $userId, ?int $month = null, ?int $year = null): void
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;
        Cache::forget("analytics.employee.{$userId}.{$year}.{$month}");
    }
}
