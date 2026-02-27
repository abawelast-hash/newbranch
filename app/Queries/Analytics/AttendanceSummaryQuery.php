<?php

namespace App\Queries\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceSummaryQuery
{
    /**
     * ملخص شهري مجمَّع لفرع أو لكل الفروع.
     * استعلام واحد بدل N+1.
     */
    public function getMonthlySummary(?int $branchId = null, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;

        return Cache::remember(
            "analytics.monthly.{$branchId}.{$year}.{$month}",
            now()->addMinutes(30),
            fn () => DB::table('attendance_logs')
                ->select([
                    'branch_id',
                    DB::raw('COUNT(DISTINCT user_id)                                        AS total_employees'),
                    DB::raw('SUM(CASE WHEN status = "on_time" THEN 1 ELSE 0 END)            AS on_time_count'),
                    DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END)               AS late_count'),
                    DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END)             AS absent_count'),
                    DB::raw('COALESCE(SUM(cost), 0)                                         AS total_cost'),
                    DB::raw('AVG(TIMESTAMPDIFF(MINUTE, check_in, check_out))                AS avg_duration_minutes'),
                    DB::raw('SUM(COALESCE(late_minutes, 0))                                 AS total_late_minutes'),
                ])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereYear('date',  $year)
                ->whereMonth('date', $month)
                ->groupBy('branch_id')
                ->get()
                ->keyBy('branch_id')
                ->toArray()
        );
    }

    public function invalidate(?int $branchId = null, ?int $month = null, ?int $year = null): void
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;
        Cache::forget("analytics.monthly.{$branchId}.{$year}.{$month}");
    }
}
