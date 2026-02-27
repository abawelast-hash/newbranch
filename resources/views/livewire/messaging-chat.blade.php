<div class="flex flex-col h-[calc(100vh-8rem)]">
    {{-- Chat Header --}}
    <div class="card rounded-b-none flex items-center gap-3">
        <a href="{{ route('messaging.inbox') }}" wire:navigate class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold
            {{ $conversation->type === 'group' ? 'bg-orange-500' : 'bg-orange-400' }}">
            @if($conversation->type === 'group')
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            @else
                {{ mb_substr($conversation->participants->where('id', '!=', auth()->id())->first()?->name ?? '?', 0, 1) }}
            @endif
        </div>
        <div>
            <h3 class="font-bold text-gray-800">
                @if($conversation->type === 'group')
                    {{ $conversation->title ?? __('pwa.msg_group') }}
                @else
                    {{ $conversation->participants->where('id', '!=', auth()->id())->first()?->name ?? __('pwa.msg_unknown') }}
                @endif
            </h3>
            <p class="text-xs text-gray-500">
                {{ $conversation->participants->count() }} {{ __('pwa.msg_participants') }}
            </p>
        </div>
    </div>

    {{-- Messages Area --}}
    <div class="flex-1 overflow-y-auto bg-gray-50 p-4 space-y-3" x-ref="messagesContainer"
         x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
         x-effect="$wire.messages; $nextTick(() => $el.scrollTop = $el.scrollHeight)">
        @foreach($this->messages as $message)
        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[75%] {{ $message->sender_id === auth()->id() ? 'order-2' : '' }}">
                {{-- Sender name for group chats --}}
                @if($conversation->type === 'group' && $message->sender_id !== auth()->id())
                <p class="text-xs text-gray-500 mb-1 px-1">{{ $message->sender?->name }}</p>
                @endif

                <div class="rounded-2xl px-4 py-2.5 shadow-sm
                    {{ $message->sender_id === auth()->id()
                        ? 'bg-orange-500 text-white rounded-ee-md'
                        : 'bg-white text-gray-800 rounded-es-md' }}">
                    <p class="text-sm leading-relaxed">{{ $message->body }}</p>
                    <div class="flex items-center justify-end gap-1 mt-1">
                        <span class="text-[10px] {{ $message->sender_id === auth()->id() ? 'text-orange-100' : 'text-gray-400' }}">
                            {{ $message->created_at->format('H:i') }}
                        </span>
                        @if($message->sender_id === auth()->id())
                        <span class="text-[10px] {{ $message->is_read ? 'text-blue-200' : 'text-orange-200' }}">
                            @if($message->is_read)
                                <svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/>
                                </svg>
                            @else
                                <svg class="w-3.5 h-3.5 inline" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm-5.93 6.59L6.34 7.85 4.93 9.27l5.73 5.73L22.59 3.07l-1.41-1.42L12.07 13.59z"/>
                                </svg>
                            @endif
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Message Input --}}
    <div class="card rounded-t-none border-t">
        <form wire:submit="sendMessage" class="flex items-center gap-2">
            <input type="text" wire:model="newMessage" class="input-field flex-1" placeholder="{{ __('pwa.msg_type_message') }}" autocomplete="off">
            <button type="submit" class="btn-primary !px-4 !py-3 rounded-xl" wire:loading.attr="disabled">
                <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>
    </div>
</div>
