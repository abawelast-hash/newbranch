<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilter;
use App\Models\AttendanceLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AttendanceOverview extends BaseWidget
{
    use HasDashboardFilter;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getFilterDates();

        $logs = AttendanceLog::whereBetween('attendance_date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ]);

        $totalEmployees   = (clone $logs)->count();
        $presentCount     = (clone $logs)->where('status', 'present')->count();
        $lateCount        = (clone $logs)->where('status', 'late')->count();
        $absentCount      = (clone $logs)->where('status', 'absent')->count();
        $totalDelayCost   = (clone $logs)->sum('delay_cost');
        $totalOvertimeVal = (clone $logs)->sum('overtime_value');

        $periodLabel = $this->getPeriodLabel();

        return [
            Stat::make(__('attendance.today_present'), $presentCount)
                ->description(__('attendance.out_of_total', ['total' => $totalEmployees]))
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make(__('attendance.today_late'), $lateCount)
                ->description($lateCount > 0
                    ? __('attendance.late_warning')
                    : __('attendance.no_late'))
                ->color($lateCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-clock'),

            Stat::make(__('attendance.today_absent'), $absentCount)
                ->color($absentCount > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-x-circle'),

            Stat::make(__('attendance.today_delay_losses'), number_format($totalDelayCost, 2) . ' ' . __('attendance.sar'))
                ->description($periodLabel)
                ->color($totalDelayCost > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-banknotes'),

            Stat::make(__('attendance.today_overtime_value'), number_format($totalOvertimeVal, 2) . ' ' . __('attendance.sar'))
                ->description($periodLabel)
                ->color('info')
                ->icon('heroicon-o-arrow-trending-up'),
        ];
    }
}
