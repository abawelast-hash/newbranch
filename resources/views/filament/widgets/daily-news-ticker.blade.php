<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $highlights = $this->getBranchHighlights();
            $items = $this->getNewsItems();
        @endphp

        {{-- Per-Branch Trophy / Turtle Cards --}}
        @if(count($highlights) > 0)
            <div class="mb-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-lg">🏆</span>
                    <h3 class="font-bold text-gray-700 dark:text-gray-200">{{ __('competition.trophy_first_title') }}</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($highlights as $h)
                        <div class="rounded-xl border p-4 bg-white dark:bg-gray-800 shadow-sm">
                            <div class="font-bold text-sm text-orange-600 mb-2">{{ $h['branch'] }}</div>
                            {{-- First check-in --}}
                            <div class="flex items-center gap-2 text-sm">
                                <span>🏆</span>
                                <span class="text-green-600 font-semibold">{{ $h['first_name'] }}</span>
                                <span class="text-gray-400 text-xs">({{ $h['first_time'] }})</span>
                            </div>
                            {{-- Last check-in --}}
                            @if($h['last_name'])
                                <div class="flex items-center gap-2 text-sm mt-1">
                                    <span>🐢</span>
                                    <span class="text-red-500 font-semibold">{{ $h['last_name'] }}</span>
                                    <span class="text-gray-400 text-xs">({{ $h['last_time'] }})</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-sm mt-1 text-gray-400">
                                    <span>🐢</span>
                                    <span>{{ __('competition.no_turtles') }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Scrolling Ticker --}}
        @if(count($items) > 0)
            <div class="overflow-hidden relative" dir="rtl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📰</span>
                    <h3 class="font-bold text-gray-700 dark:text-gray-200">{{ __('competition.news_ticker_title') }}</h3>
                </div>

                {{-- CSS-based ticker — better battery life on mobile --}}
                <style>
                    @keyframes ticker-scroll {
                        0%   { transform: translateX(0); }
                        100% { transform: translateX(-33.333%); }
                    }
                    .ticker-track {
                        animation: ticker-scroll 30s linear infinite;
                    }
                    .ticker-track:hover {
                        animation-play-state: paused;
                    }
                    /* Pause on mobile to save battery */
                    @media (max-width: 639px) {
                        .ticker-track {
                            animation-duration: 45s; /* slower on mobile */
                        }
                    }
                    /* Respect reduced-motion preference */
                    @media (prefers-reduced-motion: reduce) {
                        .ticker-track {
                            animation: none !important;
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                        }
                    }
                </style>

                <div class="overflow-hidden">
                    <div
                        class="ticker-track flex items-center gap-8 whitespace-nowrap"
                    >
                        @for($i = 0; $i < 3; $i++)
                            @foreach($items as $item)
                                <span class="inline-flex items-center gap-2 text-sm font-medium {{ $item['color'] }}">
                                    <span class="text-lg">{{ $item['icon'] }}</span>
                                    {{ $item['text'] }}
                                </span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                            @endforeach
                        @endfor
                    </div>
                </div>
            </div>
        @else
            <div class="text-center text-gray-400 py-4">
                {{ __('competition.no_news') }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
