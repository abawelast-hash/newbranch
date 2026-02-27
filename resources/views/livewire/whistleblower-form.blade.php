<div class="max-w-2xl mx-auto space-y-6">
    @if(!$submitted)
    {{-- Report Form --}}
    <div class="card">
        <div class="card-header flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ __('pwa.wb_title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('pwa.wb_subtitle') }}</p>
            </div>
        </div>

        {{-- Security Notice --}}
        <div class="mb-6 p-4 bg-orange-50 rounded-xl text-sm text-orange-800">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <div>
                    <p class="font-bold">{{ __('pwa.wb_security_title') }}</p>
                    <p class="mt-1">{{ __('pwa.wb_security_body') }}</p>
                </div>
            </div>
        </div>

        <form wire:submit="submit" class="space-y-5">
            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pwa.wb_category') }}</label>
                <select wire:model="category" class="input-field">
                    <option value="">{{ __('pwa.wb_select_category') }}</option>
                    <option value="fraud">{{ __('pwa.wb_cat_fraud') }}</option>
                    <option value="harassment">{{ __('pwa.wb_cat_harassment') }}</option>
                    <option value="corruption">{{ __('pwa.wb_cat_corruption') }}</option>
                    <option value="safety">{{ __('pwa.wb_cat_safety') }}</option>
                </select>
                @error('category') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Severity --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pwa.wb_severity') }}</label>
                <select wire:model="severity" class="input-field">
                    <option value="low">{{ __('pwa.wb_sev_low') }}</option>
                    <option value="medium">{{ __('pwa.wb_sev_medium') }}</option>
                    <option value="high">{{ __('pwa.wb_sev_high') }}</option>
                    <option value="critical">{{ __('pwa.wb_sev_critical') }}</option>
                </select>
                @error('severity') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Content --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('pwa.wb_content') }}</label>
                <textarea wire:model="content" rows="6" class="input-field" placeholder="{{ __('pwa.wb_content_placeholder') }}"></textarea>
                @error('content') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn-danger w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('pwa.wb_submit') }}</span>
                <span wire:loading class="animate-pulse">{{ __('pwa.loading') }}...</span>
            </button>
        </form>
    </div>

    {{-- Track Link --}}
    <div class="text-center">
        <a href="{{ route('whistleblower.track') }}" class="text-sm text-emerald-600 hover:underline">
            {{ __('pwa.wb_track_link') }}
        </a>
    </div>

    @else
    {{-- Success: Show Ticket & Token --}}
    <div class="card text-center space-y-6">
        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">{{ __('pwa.wb_success_title') }}</h2>
            <p class="text-sm text-gray-500">{{ __('pwa.wb_success_body') }}</p>
        </div>

        {{-- Ticket Number --}}
        <div class="p-4 bg-gray-50 rounded-xl">
            <p class="text-sm text-gray-500 mb-1">{{ __('pwa.wb_ticket') }}</p>
            <p class="text-lg font-bold font-mono text-gray-800">{{ $ticketNumber }}</p>
        </div>

        {{-- Anonymous Token --}}
        <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
            <p class="text-sm text-amber-700 font-bold mb-1">{{ __('pwa.wb_secret_token') }}</p>
            <p class="text-xs font-mono text-amber-900 break-all">{{ $anonymousToken }}</p>
            <p class="text-xs text-amber-600 mt-2">{{ __('pwa.wb_token_warning') }}</p>
        </div>

        <a href="{{ route('whistleblower.form') }}" class="btn-secondary" wire:navigate>
            {{ __('pwa.wb_new_report') }}
        </a>
    </div>
    @endif
</div>
