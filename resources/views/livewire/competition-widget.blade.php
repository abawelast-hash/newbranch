<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        {{ __('pwa.competition_title') }}
    </div>

    {{-- My Branch Rank --}}
    @if($myBranch)
    <div class="mb-4 p-3 rounded-xl border-2 {{ match($myBranchLevel) {
        'legendary' => 'border-amber-400 bg-amber-50',
        'diamond'   => 'border-cyan-400 bg-cyan-50',
        'gold'      => 'border-yellow-400 bg-yellow-50',
        'silver'    => 'border-gray-300 bg-gray-50',
        'bronze'    => 'border-orange-400 bg-orange-50',
        default     => 'border-gray-200 bg-gray-50',
    } }}">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold {{ match($myBranchLevel) {
                    'legendary' => 'text-amber-600',
                    'diamond'   => 'text-cyan-600',
                    'gold'      => 'text-yellow-600',
                    'silver'    => 'text-gray-500',
                    'bronze'    => 'text-orange-600',
                    default     => 'text-gray-400',
                } }}">#{{ $myBranchRank }}</span>
                <div>
                    <div class="font-semibold text-gray-800">{{ $myBranch['name'] }}</div>
                    <div class="text-xs text-gray-500">{{ __('pwa.your_branch') }}</div>
                </div>
            </div>
            <div class="text-center">
                <div class="text-lg">
                    @switch($myBranchLevel)
                        @case('legendary') 👑 @break
                        @case('diamond')   💎 @break
                        @case('gold')      🥇 @break
                        @case('silver')    🥈 @break
                        @case('bronze')    🥉 @break
                        @default           🏁
                    @endswitch
                </div>
                <div class="text-xs font-medium {{ match($myBranchLevel) {
                    'legendary' => 'text-amber-600',
                    'diamond'   => 'text-cyan-600',
                    'gold'      => 'text-yellow-600',
                    'silver'    => 'text-gray-500',
                    'bronze'    => 'text-orange-600',
                    default     => 'text-gray-400',
                } }}">{{ __('competition.level_' . $myBranchLevel) }}</div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div>
                <div class="text-sm font-bold text-gray-800">{{ number_format($myBranch['score']) }}</div>
                <div class="text-xs text-gray-500">{{ __('competition.score') }}</div>
            </div>
            <div>
                <div class="text-sm font-bold text-gray-800">{{ $myBranch['perfect_employees'] }}</div>
                <div class="text-xs text-gray-500">{{ __('competition.perfect_employees') }}</div>
            </div>
            <div>
                <div class="text-sm font-bold {{ $myBranch['financial_loss'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ number_format($myBranch['financial_loss'], 0) }}
                </div>
                <div class="text-xs text-gray-500">{{ __('competition.financial_loss') }} ({{ __('competition.sar') }})</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Top 3 Branches --}}
    <div>
        <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">{{ __('pwa.top_branches') }}</h4>
        <div class="space-y-2">
            @foreach($topBranches as $index => $branch)
                <div class="flex items-center justify-between p-2 rounded-lg {{ $myBranch && $branch['id'] === $myBranch['id'] ? 'bg-emerald-50 border border-emerald-200' : 'bg-gray-50' }}">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold {{ match($index) {
                            0 => 'bg-yellow-100 text-yellow-700',
                            1 => 'bg-gray-100 text-gray-600',
                            2 => 'bg-orange-100 text-orange-700',
                            default => 'bg-gray-100 text-gray-500',
                        } }}">{{ $index + 1 }}</span>
                        <span class="text-sm font-medium text-gray-800">{{ $branch['name'] }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span>{{ number_format($branch['score']) }} {{ __('competition.score') }}</span>
                        <span class="{{ $branch['financial_loss'] > 0 ? 'text-red-500' : 'text-emerald-500' }}">
                            {{ number_format($branch['financial_loss'], 0) }} {{ __('competition.sar') }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($totalBranches === 0)
        <p class="text-sm text-gray-400 text-center py-4">{{ __('competition.no_branches') }}</p>
    @endif
</div>
