<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilter;
use App\Models\Branch;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class EfficiencyScoreCardWidget extends Widget
{
    use HasDashboardFilter;

    protected static string $view = 'filament.widgets.efficiency-score-card';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        [$startDate, $endDate] = $this->getFilterDates();

        $service  = app(AnalyticsService::class);
        $branches = Branch::where('is_active', true)->get();
        $scores   = [];

        foreach ($branches as $branch) {
            $efficiency = $service->calculateEfficiencyScore(
                $branch,
                $startDate,
                $endDate
            );

            $vpm = $service->calculateVPM($branch);
            $gap = $service->calculateProductivityGap($branch, $endDate);

            $scores[] = [
                'id'         => $branch->id,
                'name'       => $branch->name_ar,
                'efficiency' => round($efficiency, 1),
                'vpm'        => round($vpm, 4),
                'gap'        => round($gap, 1),
                'target'     => (float) ($branch->target_attendance_rate ?? 95),
                'budget'     => (float) ($branch->monthly_salary_budget ?? 0),
            ];
        }

        usort($scores, fn ($a, $b) => $b['efficiency'] <=> $a['efficiency']);

        return [
            'scores'      => $scores,
            'periodLabel' => $this->getPeriodLabel(),
        ];
    }
}
