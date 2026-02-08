<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BranchLeaderboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.branch-leaderboard';

    public static function getNavigationGroup(): ?string
    {
        return __('competition.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('competition.leaderboard_title');
    }

    public function getTitle(): string
    {
        return __('competition.leaderboard_title');
    }

    /**
     * Calculate branch discipline scores and rank them.
     *
     * Scoring Formula:
     * - Base: 100 points per branch
     * - Deductions: -2 per late check-in across all employees
     * - Deductions: -5 per missed day (no check-in at all)
     * - Bonus: +10 per employee with zero lates in the month
     * - Bonus: +3 per employee total_points (gamification)
     */
    public function getBranches(): array
    {
        $branches = Branch::where('is_active', true)
            ->withCount('users')
            ->get();

        $startOfMonth = now()->startOfMonth();
        $endOfMonth   = now()->endOfMonth();
        $workingDaysElapsed = max(1, now()->day);

        $ranked = [];

        foreach ($branches as $branch) {
            $employees = User::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->get();

            $employeeCount = $employees->count();
            if ($employeeCount === 0) {
                continue;
            }

            // Count late check-ins this month
            $lateCheckins = DB::table('attendance_logs')
                ->where('branch_id', $branch->id)
                ->whereBetween('check_in_time', [$startOfMonth, $endOfMonth])
                ->where('delay_minutes', '>', 0)
                ->count();

            // Count total check-ins this month
            $totalCheckins = DB::table('attendance_logs')
                ->where('branch_id', $branch->id)
                ->whereBetween('check_in_time', [$startOfMonth, $endOfMonth])
                ->count();

            // Expected check-ins = employees × working days elapsed
            $expectedCheckins = $employeeCount * $workingDaysElapsed;
            $missedDays = max(0, $expectedCheckins - $totalCheckins);

            // Employees with zero lates
            $perfectEmployees = 0;
            foreach ($employees as $emp) {
                $hasLate = DB::table('attendance_logs')
                    ->where('user_id', $emp->id)
                    ->whereBetween('check_in_time', [$startOfMonth, $endOfMonth])
                    ->where('delay_minutes', '>', 0)
                    ->exists();

                if (!$hasLate) {
                    $perfectEmployees++;
                }
            }

            // Total gamification points
            $totalPoints = $employees->sum('total_points');

            // Calculate discipline score
            $score = 100;
            $score -= ($lateCheckins * 2);
            $score -= ($missedDays * 5);
            $score += ($perfectEmployees * 10);
            $score += round($totalPoints * 0.1);

            // Determine level
            $level = match (true) {
                $score >= 150 => ['name' => __('competition.level_legendary'), 'icon' => '🏆', 'color' => 'text-yellow-500', 'bg' => 'bg-yellow-50 border-yellow-300'],
                $score >= 120 => ['name' => __('competition.level_diamond'),   'icon' => '💎', 'color' => 'text-blue-500',   'bg' => 'bg-blue-50 border-blue-300'],
                $score >= 100 => ['name' => __('competition.level_gold'),      'icon' => '🥇', 'color' => 'text-amber-500',  'bg' => 'bg-amber-50 border-amber-300'],
                $score >= 80  => ['name' => __('competition.level_silver'),    'icon' => '🥈', 'color' => 'text-gray-400',   'bg' => 'bg-gray-50 border-gray-300'],
                $score >= 60  => ['name' => __('competition.level_bronze'),    'icon' => '🥉', 'color' => 'text-orange-600', 'bg' => 'bg-orange-50 border-orange-300'],
                default       => ['name' => __('competition.level_starter'),   'icon' => '🐢', 'color' => 'text-red-500',    'bg' => 'bg-red-50 border-red-300'],
            };

            $ranked[] = [
                'branch'            => $branch,
                'score'             => $score,
                'level'             => $level,
                'employee_count'    => $employeeCount,
                'late_checkins'     => $lateCheckins,
                'missed_days'       => $missedDays,
                'perfect_employees' => $perfectEmployees,
                'total_points'      => $totalPoints,
            ];
        }

        // Sort by score descending
        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Assign rank + trophy/turtle
        foreach ($ranked as $i => &$item) {
            $item['rank'] = $i + 1;
            if ($i === 0) {
                $item['badge'] = '🏆';
                $item['badge_label'] = __('competition.trophy_winner');
            } elseif ($i === count($ranked) - 1 && count($ranked) > 1) {
                $item['badge'] = '🐢';
                $item['badge_label'] = __('competition.turtle_last');
            } else {
                $item['badge'] = '';
                $item['badge_label'] = '';
            }
        }

        return $ranked;
    }
}
