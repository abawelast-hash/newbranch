<x-filament-widgets::widget>
    <x-filament::section>
        @php $items = $this->getNewsItems(); @endphp

        @if(count($items) > 0)
            <div class="overflow-hidden relative" dir="rtl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">📰</span>
                    <h3 class="font-bold text-gray-700 dark:text-gray-200">{{ __('competition.news_ticker_title') }}</h3>
                </div>

                <div
                    x-data="{
                        offset: 0,
                        speed: 1,
                        items: {{ json_encode(count($items)) }},
                        init() {
                            setInterval(() => {
                                this.offset -= this.speed;
                                const container = this.$refs.ticker;
                                if (container && Math.abs(this.offset) > container.scrollWidth / 2) {
                                    this.offset = 0;
                                }
                            }, 30);
                        }
                    }"
                    class="overflow-hidden"
                >
                    <div
                        x-ref="ticker"
                        :style="'transform: translateX(' + offset + 'px)'"
                        class="flex items-center gap-8 whitespace-nowrap transition-none"
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
