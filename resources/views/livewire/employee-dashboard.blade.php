<div class="space-y-6">
    {{-- Welcome Header --}}
    <div class="card">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center">
                <span class="text-2xl font-bold text-orange-700">{{ mb_substr(auth()->user()->name_ar ?? 'U', 0, 1) }}</span>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ __('pwa.welcome') }}، {{ auth()->user()->name_ar }}</h2>
                <p class="text-sm text-gray-500">{{ now()->translatedFormat('l، d F Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Widgets Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <livewire:attendance-widget />
        <livewire:attendance-stats-widget />
        <livewire:gamification-widget />
        <livewire:competition-widget />
        <livewire:branch-progress-widget />
        <livewire:financial-widget />
        <livewire:circulars-widget />
    </div>
</div>
