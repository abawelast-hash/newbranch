<div wire:poll.60s="loadData">
    @php
        $d = $data ?? [];
        $totalLoss = $d['total_loss_today'] ?? 0;
        $totalDelay = $d['total_delay_minutes'] ?? 0;
        $totalAbsent = $d['total_absent'] ?? 0;
        $branches = $d['branch_breakdown'] ?? [];
    @endphp

    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-l from-red-600 to-red-800 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center animate-pulse">
                        <x-heroicon-o-clock class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ __('analytics.lost_opportunity_clock') }}</h3>
                        <p class="text-sm text-white/70">{{ __('analytics.cumulative_losses_today') }}</p>
                    </div>
                </div>
                <div class="text-left">
                    <div class="text-xl sm:text-3xl font-black text-white tabular-nums">
                        {{ number_format($totalLoss, 2) }}
                    </div>
                    <div class="text-xs text-white/70">{{ __('analytics.sar_currency') }}</div>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-px bg-gray-200 dark:bg-gray-700">
            <div class="bg-white dark:bg-gray-900 p-3 sm:p-4 text-center">
                <div class="text-lg sm:text-2xl font-bold text-red-600 tabular-nums">{{ number_format($totalLoss, 0) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('analytics.total_losses_sar') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 p-3 sm:p-4 text-center">
                <div class="text-lg sm:text-2xl font-bold text-amber-600 tabular-nums">{{ number_format($totalDelay) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('analytics.delay_minutes_label') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 p-3 sm:p-4 text-center col-span-2 sm:col-span-1">
                <div class="text-lg sm:text-2xl font-bold text-gray-700 dark:text-gray-300 tabular-nums">{{ $totalAbsent }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('analytics.absent_today') }}</div>
            </div>
        </div>

        {{-- Branch Breakdown (hidden on mobile — details shown on desktop only) --}}
        @if(count($branches) > 0)
            <div class="hidden sm:block p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('analytics.top_losing_branches') }}</h4>
                <div class="space-y-2">
                    @foreach($branches as $i => $branch)
                        @php
                            $maxLoss = collect($branches)->max('loss') ?: 1;
                            $percentage = ($branch['loss'] / $maxLoss) * 100;
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-gray-500 w-6">{{ $i + 1 }}</span>
                            <div class="flex-1">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $branch['name'] }}</span>
                                    <span class="text-red-600 font-bold tabular-nums">{{ number_format($branch['loss'], 0) }} {{ __('command.sar') }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-gradient-to-l from-red-500 to-red-600 h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Timestamp --}}
        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 text-center">
            <span class="text-xs text-gray-400">{{ __('analytics.last_update') }}: {{ $d['timestamp'] ?? now()->toDateTimeString() }}</span>
        </div>
    </div>
</div>
