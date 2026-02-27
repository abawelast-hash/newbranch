<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        {{ __('pwa.attendance_stats_title') }}
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-4 gap-2 mb-4">
        <div class="stat-card">
            <div class="stat-value text-emerald-600">{{ $presentDays }}</div>
            <div class="stat-label">{{ __('pwa.present') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-amber-500">{{ $lateDays }}</div>
            <div class="stat-label">{{ __('pwa.late') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-red-500">{{ $absentDays }}</div>
            <div class="stat-label">{{ __('pwa.absent') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-blue-500">{{ $onTimeStreak }}</div>
            <div class="stat-label">{{ __('pwa.streak') }}</div>
        </div>
    </div>

    {{-- Extra Metrics --}}
    <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="text-center p-2 bg-gray-50 rounded-lg">
            <div class="text-sm font-bold text-gray-700">
                {{ $avgDelayMinutes > 0 ? number_format($avgDelayMinutes, 1) : '0' }}
            </div>
            <div class="text-xs text-gray-500">{{ __('pwa.avg_delay') }} ({{ __('attendance.min') }})</div>
        </div>
        <div class="text-center p-2 bg-gray-50 rounded-lg">
            <div class="text-sm font-bold text-gray-700">
                {{ $totalWorkedMinutes > 0 ? number_format($totalWorkedMinutes / 60, 1) : '0' }}
            </div>
            <div class="text-xs text-gray-500">{{ __('pwa.hours_worked') }}</div>
        </div>
        <div class="text-center p-2 bg-gray-50 rounded-lg">
            <div class="text-sm font-bold text-emerald-600">
                {{ $totalOvertimeMinutes > 0 ? number_format($totalOvertimeMinutes / 60, 1) : '0' }}
            </div>
            <div class="text-xs text-gray-500">{{ __('pwa.overtime_hours') }}</div>
        </div>
    </div>

    {{-- Weekly Breakdown --}}
    @if(count($weeklyBreakdown) > 0)
    <div>
        <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">{{ __('pwa.weekly_breakdown') }}</h4>
        <div class="space-y-2">
            @foreach($weeklyBreakdown as $week)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-14 shrink-0">{{ $week['label'] }}</span>
                    <div class="flex-1">
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500 {{ $week['rate'] >= 90 ? 'bg-emerald-500' : ($week['rate'] >= 70 ? 'bg-amber-400' : 'bg-red-500') }}"
                                 style="width: {{ $week['rate'] }}%"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-xs shrink-0">
                        <span class="text-emerald-600 font-medium">{{ $week['on_time'] }}</span>
                        @if($week['late'] > 0)
                            <span class="text-amber-500">/ {{ $week['late'] }}</span>
                        @endif
                        @if($week['absent'] > 0)
                            <span class="text-red-500">/ {{ $week['absent'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="flex justify-end gap-3 mt-1 text-xs text-gray-400">
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ __('pwa.present') }}</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>{{ __('pwa.late') }}</span>
            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span>{{ __('pwa.absent') }}</span>
        </div>
    </div>
    @else
        <p class="text-sm text-gray-400 text-center py-2">{{ __('pwa.no_attendance_data') }}</p>
    @endif
</div>
