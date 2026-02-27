<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">{{ __('pwa.messaging_title') }}</h2>
        <span class="bg-orange-100 text-orange-800 text-xs font-bold px-2.5 py-1 rounded-full">
            {{ $this->totalUnread }}
        </span>
    </div>

    {{-- Conversations List --}}
    <div class="space-y-3">
        @forelse($this->conversations as $conversation)
        <a href="{{ route('messaging.chat', $conversation) }}" wire:navigate
           class="card block hover:shadow-md transition-shadow duration-200 {{ $conversation->unread_count > 0 ? 'border-s-4 border-orange-500' : '' }}">
            <div class="flex items-center gap-3">
                {{-- Avatar --}}
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0
                    {{ $conversation->type === 'group' ? 'bg-orange-500' : 'bg-orange-400' }}">
                    @if($conversation->type === 'group')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    @else
                        {{ mb_substr($conversation->participants->where('id', '!=', auth()->id())->first()?->name ?? '?', 0, 1) }}
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold text-gray-800 truncate">
                            @if($conversation->type === 'group')
                                {{ $conversation->title ?? __('pwa.msg_group') }}
                            @else
                                {{ $conversation->participants->where('id', '!=', auth()->id())->first()?->name ?? __('pwa.msg_unknown') }}
                            @endif
                        </h3>
                        @if($conversation->latestMessage)
                        <span class="text-xs text-gray-400 flex-shrink-0">
                            {{ $conversation->latestMessage->created_at->diffForHumans(short: true) }}
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm text-gray-500 truncate">
                            {{ $conversation->latestMessage?->body ?? __('pwa.msg_no_messages') }}
                        </p>
                        @if($conversation->unread_count > 0)
                        <span class="bg-emerald-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0">
                            {{ $conversation->unread_count }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </a>
        @empty
        <div class="card text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-gray-500">{{ __('pwa.msg_empty') }}</p>
        </div>
        @endforelse
    </div>
</div>
