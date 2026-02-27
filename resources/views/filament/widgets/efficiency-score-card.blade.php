<div>
    @php $scores = $scores ?? []; @endphp

    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-orange-600" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('analytics.efficiency_score_card') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('analytics.branch_comparison') }} — {{ now()->translatedFormat('F Y') }}</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{{ __('analytics.branch_label') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('analytics.efficiency_label') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('analytics.vpm_label') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('analytics.productivity_gap_label') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">{{ __('analytics.status_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($scores as $i => $score)
                        @php
                            $eff = $score['efficiency'];
                            $statusColor = match(true) {
                                $eff >= 85 => 'emerald',
                                $eff >= 70 => 'amber',
                                $eff >= 50 => 'orange',
                                default => 'red',
                            };
                            $statusLabel = match(true) {
                                $eff >= 85 => __('analytics.grade_excellent'),
                                $eff >= 70 => __('analytics.grade_good'),
                                $eff >= 50 => __('analytics.grade_acceptable'),
                                default => __('analytics.grade_critical'),
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-right">
                                @if($i === 0)
                                    <span class="text-lg">🥇</span>
                                @elseif($i === 1)
                                    <span class="text-lg">🥈</span>
                                @elseif($i === 2)
                                    <span class="text-lg">🥉</span>
                                @else
                                    <span class="text-gray-400">{{ $i + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                {{ $score['name'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-{{ $statusColor }}-500 h-2 rounded-full transition-all duration-500"
                                             style="width: {{ min($eff, 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-{{ $statusColor }}-600 tabular-nums">{{ $eff }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 tabular-nums">
                                {{ number_format($score['vpm'], 4) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="tabular-nums {{ $score['gap'] > 10 ? 'text-red-600 font-bold' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $score['gap'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800
                                    dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-300">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                {{ __('analytics.no_data_available') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
