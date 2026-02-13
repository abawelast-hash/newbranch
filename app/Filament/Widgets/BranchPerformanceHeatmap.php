<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardFilter;
use App\Models\AttendanceLog;
use App\Models\Branch;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BranchPerformanceHeatmap extends BaseWidget
{
    use HasDashboardFilter;

    protected static ?string $heading = null;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('command.branch_heatmap_title') . ' — ' . $this->getPeriodLabel();
    }

    /**
     * Compute branch performance inline using filtered date range.
     */
    private function computePerformance(): \Illuminate\Support\Collection
    {
        [$startDate, $endDate] = $this->getFilterDates();
        $start = $startDate->toDateString();
        $end   = $endDate->toDateString();

        $branches = Branch::active()->get();

        return $branches->map(function (Branch $branch) use ($start, $end) {
            $logs = AttendanceLog::where('branch_id', $branch->id)
                ->whereBetween('attendance_date', [$start, $end])
                ->get();

            $totalLogs          = $logs->count();
            $onTimeCount        = $logs->where('status', 'present')->count();
            $lateCount          = $logs->where('status', 'late')->count();
            $absentCount        = $logs->where('status', 'absent')->count();
            $geofenceViolations = $logs->where('check_in_within_geofence', false)->count();
            $totalLoss          = (float) $logs->sum('delay_cost');
            $totalEmployees     = $branch->users()->active()->count();

            $onTimeRate = $totalLogs > 0
                ? round(($onTimeCount / $totalLogs) * 100, 1)
                : 0;

            $geofenceCompliance = $totalLogs > 0
                ? round((($totalLogs - $geofenceViolations) / $totalLogs) * 100, 1)
                : 100;

            $grade = match (true) {
                $onTimeRate >= 95 => 'excellent',
                $onTimeRate >= 85 => 'good',
                $onTimeRate >= 70 => 'average',
                default           => 'poor',
            };

            return [
                'branch_id'           => $branch->id,
                'total_employees'     => $totalEmployees,
                'on_time_rate'        => $onTimeRate,
                'geofence_compliance' => $geofenceCompliance,
                'total_loss'          => $totalLoss,
                'grade'               => $grade,
            ];
        });
    }

    public function table(Table $table): Table
    {
        $performance = $this->computePerformance();

        return $table
            ->query(
                Branch::query()->active()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('command.branch_name'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_employees')
                    ->label(__('command.total_employees'))
                    ->state(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return $data['total_employees'] ?? 0;
                    }),

                Tables\Columns\TextColumn::make('on_time_rate')
                    ->label(__('command.on_time_rate'))
                    ->state(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['on_time_rate'] ?? 0) . '%';
                    })
                    ->color(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $rate = $data['on_time_rate'] ?? 0;
                        return match (true) {
                            $rate >= 95 => 'success',
                            $rate >= 85 => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('geofence_compliance')
                    ->label(__('command.geofence_compliance'))
                    ->state(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['geofence_compliance'] ?? 100) . '%';
                    })
                    ->color(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $rate = $data['geofence_compliance'] ?? 100;
                        return match (true) {
                            $rate >= 95 => 'success',
                            $rate >= 85 => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('monthly_loss')
                    ->label(__('command.monthly_loss'))
                    ->state(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return number_format($data['total_loss'] ?? 0, 2) . ' ' . __('command.sar');
                    })
                    ->color(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        return ($data['total_loss'] ?? 0) > 0 ? 'danger' : 'success';
                    }),

                Tables\Columns\TextColumn::make('grade')
                    ->label(__('command.performance_grade'))
                    ->state(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $grade = $data['grade'] ?? 'average';
                        return __('command.grade_' . $grade);
                    })
                    ->color(function (Branch $record) use ($performance) {
                        $data = $performance->firstWhere('branch_id', $record->id);
                        $grade = $data['grade'] ?? 'average';
                        return match ($grade) {
                            'excellent' => 'success',
                            'good'      => 'info',
                            'average'   => 'warning',
                            default     => 'danger',
                        };
                    })
                    ->badge(),
            ])
            ->paginated(false);
    }
}
