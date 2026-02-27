<div class="card">
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
        </svg>
        {{ __('pwa.circulars_title') }}
    </div>

    @if(count($circulars) > 0)
        <div class="space-y-3">
            @foreach($circulars as $circular)
                <div class="p-4 rounded-xl border {{ $circular['acknowledged'] ? 'border-gray-100 bg-gray-50' : 'border-orange-200 bg-orange-50' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                @if($circular['priority'] === 'urgent')
                                    <span class="badge-danger">{{ __('pwa.urgent') }}</span>
                                @elseif($circular['priority'] === 'important')
                                    <span class="badge-warning">{{ __('pwa.important') }}</span>
                                @endif
                                <h4 class="text-sm font-bold text-gray-800 truncate">{{ $circular['title'] }}</h4>
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-2">{!! nl2br(e(Str::limit($circular['body'], 120))) !!}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $circular['published_at'] }}</p>
                        </div>

                        @if($circular['requires_ack'] && !$circular['acknowledged'])
                            <button wire:click="acknowledge({{ $circular['id'] }})"
                                    class="btn-primary text-xs whitespace-nowrap !px-3 !py-1.5">
                                {{ __('pwa.acknowledge') }}
                            </button>
                        @elseif($circular['acknowledged'])
                            <span class="text-emerald-600 text-xs whitespace-nowrap">✓ {{ __('pwa.acknowledged') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-400 text-center py-4">{{ __('pwa.no_circulars') }}</p>
    @endif
</div>
