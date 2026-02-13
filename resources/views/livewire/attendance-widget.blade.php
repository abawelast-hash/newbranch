<div class="card" x-data="{
    latitude: null,
    longitude: null,
    geoError: null,
    geoLoading: true,
    watchId: null,

    init() {
        if (navigator.geolocation) {
            this.watchId = navigator.geolocation.watchPosition(
                pos => {
                    this.latitude = pos.coords.latitude;
                    this.longitude = pos.coords.longitude;
                    this.geoLoading = false;
                    $wire.updateGeolocation(this.latitude, this.longitude);
                },
                err => {
                    this.geoError = err.message;
                    this.geoLoading = false;
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        } else {
            this.geoError = '{{ __('pwa.gps_error') }}';
            this.geoLoading = false;
        }
    },

    destroy() {
        if (this.watchId) navigator.geolocation.clearWatch(this.watchId);
    }
}">
    {{-- Header --}}
    <div class="card-header flex items-center gap-2">
        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ __('pwa.attendance_title') }}
    </div>

    {{-- GPS Status Panel --}}
    <div class="mb-4 p-3 rounded-xl border {{ $isInsideGeofence ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
        {{-- Loading state --}}
        <template x-if="geoLoading">
            <div class="flex items-center gap-2 text-gray-500">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm">{{ __('pwa.gps_acquiring') }}</span>
            </div>
        </template>

        {{-- GPS Ready --}}
        <template x-if="!geoLoading && !geoError">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        @if($isInsideGeofence)
                            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            <span class="text-sm font-semibold text-emerald-700">{{ __('pwa.inside_geofence') }}</span>
                        @else
                            <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
                            <span class="text-sm font-semibold text-red-700">{{ __('pwa.outside_geofence') }}</span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 font-mono">
                        {{ $distanceMeters }}{{ __('pwa.meters') }} / {{ $geofenceRadius }}{{ __('pwa.meters') }}
                    </span>
                </div>

                {{-- Distance Progress Bar --}}
                @if($geofenceRadius > 0)
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        @php
                            $pct = min(100, ($distanceMeters / $geofenceRadius) * 100);
                            $barColor = $isInsideGeofence ? 'bg-emerald-500' : 'bg-red-500';
                        @endphp
                        <div class="{{ $barColor }} h-full rounded-full transition-all duration-500"
                             style="width: {{ $pct }}%"></div>
                    </div>
                @endif
            </div>
        </template>

        {{-- Geo Error --}}
        <template x-if="geoError">
            <div class="flex items-center gap-2 text-amber-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span class="text-sm" x-text="geoError"></span>
            </div>
        </template>
    </div>

    {{-- Status Badge --}}
    <div class="flex items-center gap-3 mb-4">
        @if($status === 'checked_in')
            <span class="badge-success">
                <span class="w-2 h-2 bg-emerald-500 rounded-full me-2 animate-pulse"></span>
                {{ __('pwa.status_checked_in') }}
            </span>
            <span class="text-sm text-gray-500">{{ $checkInTime }}</span>
        @elseif($status === 'checked_out')
            <span class="badge-warning">
                {{ __('pwa.status_checked_out') }}
            </span>
            <span class="text-sm text-gray-500">{{ $checkInTime }} → {{ $checkOutTime }}</span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                {{ __('pwa.status_not_checked_in') }}
            </span>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-3">
        @if($status === 'not_checked_in')
            <button
                @click="if(latitude) $wire.checkIn(latitude, longitude)"
                :disabled="!latitude"
                class="btn-primary text-sm flex-1 flex items-center justify-center gap-2"
                :class="{ 'opacity-50 cursor-not-allowed': !latitude }">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                {{ __('pwa.btn_check_in') }}
            </button>
        @elseif($status === 'checked_in')
            <button
                @click="if(latitude) $wire.checkOut(latitude, longitude)"
                :disabled="!latitude"
                class="btn-secondary text-sm flex-1 flex items-center justify-center gap-2"
                :class="{ 'opacity-50 cursor-not-allowed': !latitude }">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('pwa.btn_check_out') }}
            </button>
        @else
            <div class="w-full text-center py-2 text-sm text-gray-500 bg-gray-50 rounded-lg">
                {{ __('pwa.btn_done') }} ✓
            </div>
        @endif
    </div>

    {{-- Messages --}}
    @if($message)
        <div class="mt-3 text-sm rounded-lg px-3 py-2 {{ $messageType === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
            {{ $message }}
        </div>
    @endif

    {{-- Separator --}}
    <hr class="my-4 border-gray-200">

    {{-- Whistleblower Secret Report Button --}}
    <div>
        <button
            wire:click="toggleWhistleblowerForm"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-medium transition-all duration-300
                   {{ $showWhistleblowerForm ? 'bg-red-600 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-700' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            {{ __('pwa.wb_title') }}
        </button>

        {{-- Whistleblower Form --}}
        @if($showWhistleblowerForm)
            <div class="mt-3 p-4 rounded-xl border border-red-200 bg-red-50/50 animate-fadeInUp">
                @if($wbTicket)
                    {{-- Success: Show ticket --}}
                    <div class="text-center space-y-3">
                        <div class="w-12 h-12 mx-auto bg-emerald-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h4 class="font-bold text-emerald-800">{{ __('pwa.wb_success_title') }}</h4>
                        <div class="bg-white rounded-lg p-3 space-y-2">
                            <div class="text-xs text-gray-500">{{ __('pwa.wb_ticket') }}</div>
                            <div class="font-mono font-bold text-lg">{{ $wbTicket }}</div>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 space-y-2">
                            <div class="text-xs text-amber-700 font-semibold">{{ __('pwa.wb_secret_token') }}</div>
                            <div class="font-mono text-sm break-all select-all">{{ $wbToken }}</div>
                            <div class="text-xs text-amber-600">{{ __('pwa.wb_token_warning') }}</div>
                        </div>
                        <button wire:click="toggleWhistleblowerForm" class="btn-primary text-sm">
                            {{ __('pwa.wb_new_report') }}
                        </button>
                    </div>
                @else
                    {{-- Security notice --}}
                    <div class="mb-3 p-2 rounded-lg bg-white/60 text-xs text-gray-600 space-y-1">
                        <div class="font-semibold text-red-700 mb-1">{{ __('pwa.wb_security_title') }}</div>
                        <div>🔒 {{ __('pwa.wb_security_1') }}</div>
                        <div>🔐 {{ __('pwa.wb_security_2') }}</div>
                        <div>🎫 {{ __('pwa.wb_security_3') }}</div>
                    </div>

                    {{-- Form --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('pwa.wb_category') }}</label>
                            <select wire:model="wbCategory"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">{{ __('pwa.wb_select_category') }}</option>
                                <option value="fraud">{{ __('pwa.wb_cat_fraud') }}</option>
                                <option value="corruption">{{ __('pwa.wb_cat_corruption') }}</option>
                                <option value="harassment">{{ __('pwa.wb_cat_harassment') }}</option>
                                <option value="safety">{{ __('pwa.wb_cat_safety') }}</option>
                                <option value="discrimination">{{ __('pwa.wb_cat_discrimination') }}</option>
                                <option value="other">{{ __('pwa.wb_cat_other') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('pwa.wb_severity') }}</label>
                            <select wire:model="wbSeverity"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="low">{{ __('pwa.wb_sev_low') }}</option>
                                <option value="medium">{{ __('pwa.wb_sev_medium') }}</option>
                                <option value="high">{{ __('pwa.wb_sev_high') }}</option>
                                <option value="critical">{{ __('pwa.wb_sev_critical') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('pwa.wb_content') }}</label>
                            <textarea wire:model="wbContent" rows="4"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-orange-500 focus:ring-orange-500"
                                placeholder="{{ __('pwa.wb_content_placeholder') }}"></textarea>
                        </div>

                        <button wire:click="submitWhistleblowerReport"
                            class="w-full py-2.5 rounded-xl bg-red-600 text-white text-sm font-semibold hover:bg-red-700 transition-colors">
                            {{ __('pwa.wb_submit') }}
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
