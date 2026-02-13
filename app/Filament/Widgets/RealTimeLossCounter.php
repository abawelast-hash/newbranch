<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilter;
use App\Models\AttendanceLog;
use App\Services\FinancialReportingService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RealTimeLossCounter extends BaseWidget
{
    use HasDashboardFilter;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getFilterDates();
        $startStr = $startDate->toDateString();
        $endStr   = $endDate->toDateString();

        $service = app(FinancialReportingService::class);

        // Sum loss across the filtered date range
        $periodLoss = (float) AttendanceLog::whereBetween('attendance_date', [$startStr, $endStr])
            ->sum('delay_cost');

        // Comparison: same duration in the previous period
        $days         = $startDate->diffInDays($endDate) + 1;
        $prevEnd      = $startDate->copy()->subDay();
        $prevStart    = $prevEnd->copy()->subDays($days - 1);
        $previousLoss = (float) AttendanceLog::whereBetween('attendance_date', [
            $prevStart->toDateString(),
            $prevEnd->toDateString(),
        ])->sum('delay_cost');

        $lossDiff = $periodLoss - $previousLoss;
        $trendDescription = ($lossDiff >= 0 ? '+' : '-')
            . number_format(abs($lossDiff), 2) . ' ' . __('command.sar')
            . ' ' . __('dashboard.vs_previous_period');

        // Counts for the filtered period
        $logs        = AttendanceLog::whereBetween('attendance_date', [$startStr, $endStr]);
        $lateCount   = (clone $logs)->where('status', 'late')->count();
        $absentCount = (clone $logs)->where('status', 'absent')->count();

        // Predictive (always based on current month)
        $predictive = $service->getPredictiveMonthlyLoss(Carbon::now());

        $periodLabel = $this->getPeriodLabel();

        return [
            Stat::make(
                __('command.today_total_loss'),
                number_format($periodLoss, 2) . ' ' . __('command.sar')
            )
                ->description($trendDescription)
                ->descriptionIcon($lossDiff >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($periodLoss > 0 ? 'danger' : 'success')
                ->chart([$previousLoss, $periodLoss]),

            Stat::make(
                __('command.today_late_count'),
                $lateCount . ' ' . __('command.employees')
            )
                ->description($periodLabel)
                ->color($lateCount > 5 ? 'danger' : ($lateCount > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-clock'),

            Stat::make(
                __('command.today_absent_count'),
                $absentCount . ' ' . __('command.employees')
            )
                ->description($periodLabel)
                ->color($absentCount > 3 ? 'danger' : ($absentCount > 0 ? 'warning' : 'success'))
                ->icon('heroicon-o-x-circle'),

            Stat::make(
                __('command.predictive_title'),
                number_format($predictive['predicted_total'], 2) . ' ' . __('command.sar')
            )
                ->description(
                    __('command.remaining_days') . ': ' . $predictive['remaining_working_days']
                )
                ->color('info')
                ->icon('heroicon-o-chart-bar'),
        ];
    }
}
