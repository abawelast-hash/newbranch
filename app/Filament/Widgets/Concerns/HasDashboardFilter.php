<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Provides date-range helpers based on the dashboard period filter.
 *
 * Requires InteractsWithPageFilters to be used alongside this trait.
 */
trait HasDashboardFilter
{
    use InteractsWithPageFilters;

    /**
     * Convert the dashboard period filter into a [startDate, endDate] pair.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    protected function getFilterDates(): array
    {
        $period = $this->filters['period'] ?? 'today';

        return match ($period) {
            'week'   => [now()->startOfWeek(Carbon::SUNDAY), now()->endOfDay()],
            'month'  => [now()->startOfMonth(), now()->endOfDay()],
            'year'   => [now()->startOfYear(), now()->endOfDay()],
            'custom' => [
                !empty($this->filters['start_date'])
                    ? Carbon::parse($this->filters['start_date'])->startOfDay()
                    : now()->startOfDay(),
                !empty($this->filters['end_date'])
                    ? Carbon::parse($this->filters['end_date'])->endOfDay()
                    : now()->endOfDay(),
            ],
            default  => [now()->startOfDay(), now()->endOfDay()], // 'today'
        };
    }

    /**
     * Whether the current filter is for a single day.
     */
    protected function isSingleDayFilter(): bool
    {
        $period = $this->filters['period'] ?? 'today';

        if ($period === 'today') {
            return true;
        }

        if ($period === 'custom') {
            $start = $this->filters['start_date'] ?? null;
            $end   = $this->filters['end_date'] ?? null;
            return $start && $end && Carbon::parse($start)->isSameDay(Carbon::parse($end));
        }

        return false;
    }

    /**
     * Get a human-readable label for the current period.
     */
    protected function getPeriodLabel(): string
    {
        $period = $this->filters['period'] ?? 'today';

        return match ($period) {
            'today'  => __('dashboard.today'),
            'week'   => __('dashboard.this_week'),
            'month'  => __('dashboard.this_month'),
            'year'   => __('dashboard.this_year'),
            'custom' => __('dashboard.custom_range'),
            default  => __('dashboard.today'),
        };
    }
}
