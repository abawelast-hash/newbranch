<?php

namespace App\Queries\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LossCalculationQuery
{
    /**
     * حساب الخسارة المالية اليومية للفرع — استعلام مجمَّع واحد.
     */
    public function getDailyLoss(int $branchId, string $date): float
    {
        return Cache::remember(
            "analytics.loss.{$branchId}.{$date}",
            now()->addMinutes(10),
            fn () => (float) DB::table('attendance_logs')
                ->where('branch_id', $branchId)
                ->whereDate('date', $date)
                ->sum('cost')
        );
    }

    /**
     * خسارة الشهر كاملاً مجمَّعة لكل الأيام دفعة واحدة.
     */
    public function getMonthlyLossBreakdown(int $branchId, int $month, int $year): array
    {
        return Cache::remember(
            "analytics.monthly_loss.{$branchId}.{$year}.{$month}",
            now()->addMinutes(30),
            fn () => DB::table('attendance_logs')
                ->select([
                    DB::raw('DATE(date) AS day'),
                    DB::raw('COALESCE(SUM(cost), 0) AS loss'),
                    DB::raw('COUNT(DISTINCT user_id) AS employees_affected'),
                ])
                ->where('branch_id', $branchId)
                ->whereYear('date',  $year)
                ->whereMonth('date', $month)
                ->where('cost', '>', 0)
                ->groupByRaw('DATE(date)')
                ->orderBy('date')
                ->get()
                ->toArray()
        );
    }

    public function invalidate(int $branchId, string $date): void
    {
        Cache::forget("analytics.loss.{$branchId}.{$date}");
    }
}
