{{-- SARH v2.0 — Branch Progress Hero Card --}}
<div class="overflow-hidden rounded-2xl shadow-lg" style="border: 1px solid rgba(255, 140, 0, 0.15);">
    {{-- Gradient Hero --}}
    <div class="relative overflow-hidden px-6 py-6" style="background: linear-gradient(135deg, #FF8C00, #fb923c, #f59e0b);">
        {{-- Decorative --}}
        <div class="absolute top-0 right-0 w-40 h-40 rounded-full" style="background: rgba(255,255,255,0.1); transform: translate(30%, -30%);"></div>
        <div class="absolute bottom-0 left-0 w-28 h-28 rounded-full" style="background: rgba(255,255,255,0.07); transform: translate(-20%, 20%);"></div>

        @if($branchName)
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium" style="color: rgba(255,255,255,0.85);">
                        📊 إحصائيات الأسبوع · {{ $periodLabel }}
                    </p>
                    <h2 class="text-2xl font-bold text-white mt-1">{{ $branchName }}</h2>
                    <p class="text-sm mt-1" style="color: rgba(255,255,255,0.75);">{{ $branchEmployees }} {{ __('competition.employees') }}</p>
                </div>
                <div class="text-center">
                    <div class="text-5xl drop-shadow-lg">
                        @switch($currentLevel)
                            @case('legendary') 👑 @break
                            @case('diamond')   💎 @break
                            @case('gold')      🥇 @break
                            @case('silver')    🥈 @break
                            @case('bronze')    🥉 @break
                            @default           🏁
                        @endswitch
                    </div>
                    <div class="text-sm font-bold text-white mt-1">{{ __('competition.level_' . $currentLevel) }}</div>
                </div>
            </div>

            {{-- Score & Progress --}}
            <div class="mt-5">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-white font-bold text-lg">{{ number_format($currentScore) }} نقطة</span>
                    @if($nextLevel)
                        <span class="text-sm" style="color: rgba(255,255,255,0.75);">{{ __('competition.level_' . $nextLevel) }} — {{ number_format($nextLevelThreshold) }}</span>
                    @else
                        <span class="text-white font-bold text-sm">🏆 {{ __('pwa.max_level') }}</span>
                    @endif
                </div>
                <div class="h-3 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.2);">
                    <div class="h-full rounded-full transition-all duration-1000 ease-out" style="width: {{ $progressPercent }}%; background: white;"></div>
                </div>
            </div>
        </div>
        @else
        <div class="relative z-10 text-center py-4">
            <p class="text-sm" style="color: rgba(255,255,255,0.8);">{{ __('pwa.no_branch_assigned') }}</p>
        </div>
        @endif
    </div>

    {{-- Stats Grid --}}
    @if($branchName)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 bg-white dark:bg-gray-900">
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-green-50 to-orange-50 dark:from-orange-950/30 dark:to-green-950/30 border border-green-200 dark:border-green-800/30 animate-scaleIn" style="animation-delay: 0.15s">
            <div class="text-2xl font-bold {{ $attendanceRate >= 90 ? 'text-orange-600' : ($attendanceRate >= 70 ? 'text-amber-500' : 'text-red-500') }}">
                {{ $attendanceRate }}%
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ __('pwa.branch_attendance_rate') }}</div>
        </div>
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30 border border-red-200 dark:border-red-800/30 animate-scaleIn" style="animation-delay: 0.2s">
            <div class="text-2xl font-bold {{ $branchDelayCost > 0 ? 'text-red-500' : 'text-orange-600' }}">
                {{ number_format($branchDelayCost, 0) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ __('competition.financial_loss') }}</div>
        </div>
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-blue-50 to-orange-50 dark:from-blue-950/30 dark:to-orange-950/30 border border-blue-200 dark:border-blue-800/30 animate-scaleIn" style="animation-delay: 0.25s">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $perfectEmployees }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ __('competition.perfect_employees') }}</div>
        </div>
        <div class="text-center p-3 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/30 dark:to-orange-950/30 border border-amber-200 dark:border-amber-800/30 animate-scaleIn" style="animation-delay: 0.3s">
            <div class="text-2xl font-bold text-amber-500">{{ $lateCount }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">{{ __('competition.late_checkins') }}</div>
        </div>
    </div>
    @endif
</div>
