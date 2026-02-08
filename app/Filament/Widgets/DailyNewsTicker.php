<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class DailyNewsTicker extends Widget
{
    protected static string $view = 'filament.widgets.daily-news-ticker';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -1;

    /**
     * Get today's news items for the ticker.
     */
    public function getNewsItems(): array
    {
        $items = [];
        $today = now()->toDateString();

        // ── Trophy: Best branch today ──────────────────────────
        $bestBranch = $this->getBestBranchToday();
        if ($bestBranch) {
            $items[] = [
                'icon'  => '🏆',
                'text'  => __('competition.ticker_trophy', ['branch' => $bestBranch['name']]),
                'color' => 'text-yellow-600',
            ];
        }

        // ── Turtle: Worst performing branch ────────────────────
        $worstBranch = $this->getWorstBranchToday();
        if ($worstBranch) {
            $items[] = [
                'icon'  => '🐢',
                'text'  => __('competition.ticker_turtle', ['branch' => $worstBranch['name']]),
                'color' => 'text-red-500',
            ];
        }

        // ── Today's attendance stats ──────────────────────────
        $todayLates = DB::table('attendance_logs')
            ->whereDate('check_in_time', $today)
            ->where('delay_minutes', '>', 0)
            ->count();

        $todayOnTime = DB::table('attendance_logs')
            ->whereDate('check_in_time', $today)
            ->where('delay_minutes', '<=', 0)
            ->count();

        if ($todayOnTime + $todayLates > 0) {
            $items[] = [
                'icon'  => '📊',
                'text'  => __('competition.ticker_attendance', [
                    'on_time' => $todayOnTime,
                    'late'    => $todayLates,
                ]),
                'color' => 'text-blue-600',
            ];
        }

        // ── Total employees ──────────────────────────────────
        $totalEmployees = User::where('status', 'active')->count();
        $items[] = [
            'icon'  => '👥',
            'text'  => __('competition.ticker_total_employees', ['count' => $totalEmployees]),
            'color' => 'text-gray-600',
        ];

        // ── Top scorer this month ────────────────────────────
        $topScorer = User::where('status', 'active')
            ->where('total_points', '>', 0)
            ->orderByDesc('total_points')
            ->first();

        if ($topScorer) {
            $items[] = [
                'icon'  => '⭐',
                'text'  => __('competition.ticker_top_scorer', [
                    'name'   => $topScorer->name_ar,
                    'points' => $topScorer->total_points,
                ]),
                'color' => 'text-amber-600',
            ];
        }

        return $items;
    }

    private function getBestBranchToday(): ?array
    {
        $today = now()->toDateString();

        $result = DB::table('attendance_logs')
            ->join('branches', 'attendance_logs.branch_id', '=', 'branches.id')
            ->whereDate('check_in_time', $today)
            ->select('branches.id', 'branches.name_ar as name')
            ->selectRaw('AVG(delay_minutes) as avg_delay')
            ->groupBy('branches.id', 'branches.name_ar')
            ->orderBy('avg_delay', 'asc')
            ->first();

        return $result ? ['name' => $result->name, 'avg_delay' => $result->avg_delay] : null;
    }

    private function getWorstBranchToday(): ?array
    {
        $today = now()->toDateString();

        $result = DB::table('attendance_logs')
            ->join('branches', 'attendance_logs.branch_id', '=', 'branches.id')
            ->whereDate('check_in_time', $today)
            ->select('branches.id', 'branches.name_ar as name')
            ->selectRaw('AVG(delay_minutes) as avg_delay')
            ->groupBy('branches.id', 'branches.name_ar')
            ->orderBy('avg_delay', 'desc')
            ->first();

        return $result ? ['name' => $result->name, 'avg_delay' => $result->avg_delay] : null;
    }
}
