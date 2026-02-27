<?php

namespace App\Queries\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchPerformanceQuery
{
    /**
     * ترتيب الفروع حسب الأداء للفترة المحددة.
     */
    public function getRanked(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year  = $year  ?? now()->year;

        return Cache::remember(
            "analytics.branch_rank.{$year}.{$month}",
            now()->addMinutes(60),
            fn () => DB::table('attendance_logs AS a')
                ->join('branches AS b', 'b.id', '=', 'a.branch_id')
                ->select([
                    'a.branch_id',
                    'b.name AS branch_name',
                    DB::raw('COUNT(DISTINCT a.user_id)                                  AS headcount'),
                    DB::raw('ROUND(
                        SUM(CASE WHEN a.status = "on_time" THEN 1 ELSE 0 END)
                        / NULLIF(COUNT(*), 0) * 100, 1
                    )                                                                   AS on_time_rate'),
                    DB::raw('COALESCE(SUM(a.cost), 0)                                  AS total_loss'),
                    DB::raw('SUM(COALESCE(a.late_minutes, 0))                          AS late_minutes_total'),
                ])
                ->whereYear('a.date',  $year)
                ->whereMonth('a.date', $month)
                ->groupBy('a.branch_id', 'b.name')
                ->orderByDesc('on_time_rate')
                ->get()
                ->toArray()
        );
    }

    public function invalidate(?int $month = null, ?int $year = null): void
    {
        Cache::forget("analytics.branch_rank.{$year}.{$month}");
    }
}
