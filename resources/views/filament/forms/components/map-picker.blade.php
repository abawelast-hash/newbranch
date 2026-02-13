<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            lat: $wire.entangle('data.latitude'),
            lng: $wire.entangle('data.longitude'),
            radius: $wire.entangle('data.geofence_radius'),
            map: null,
            marker: null,
            circle: null,
            loaded: false,
            error: false,
            mapReady: false,

            loadLeaflet() {
                return new Promise((resolve, reject) => {
                    if (window.L) { resolve(); return; }

                    if (!document.querySelector('link[href*=\"leaflet\"]')) {
                        const css = document.createElement('link');
                        css.rel = 'stylesheet';
                        css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                        document.head.appendChild(css);
                    }

                    const js = document.createElement('script');
                    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    js.onload = () => resolve();
                    js.onerror = () => reject(new Error('فشل تحميل مكتبة الخرائط'));
                    document.head.appendChild(js);
                });
            },

            forceResize() {
                if (!this.map) return;
                this.map.invalidateSize();
                setTimeout(() => this.map?.invalidateSize(), 100);
                setTimeout(() => this.map?.invalidateSize(), 300);
                setTimeout(() => this.map?.invalidateSize(), 600);
                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                        if (this.marker) this.map.panTo(this.marker.getLatLng());
                    }
                }, 1000);
            },

            initMap() {
                if (this.mapReady) return;
                const container = this.$refs.map;
                if (!container || container.offsetHeight < 10) return;

                this.mapReady = true;
                const defaultLat = parseFloat(this.lat) || 24.7136;
                const defaultLng = parseFloat(this.lng) || 46.6753;
                const defaultRadius = parseInt(this.radius) || 100;

                this.map = L.map(container, {
                    center: [defaultLat, defaultLng],
                    zoom: 15,
                    scrollWheelZoom: true,
                    tap: true,
                    dragging: true,
                    touchZoom: true,
                    zoomControl: true,
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19,
                }).addTo(this.map);

                this.marker = L.marker([defaultLat, defaultLng], {
                    draggable: true,
                }).addTo(this.map);

                this.circle = L.circle([defaultLat, defaultLng], {
                    radius: defaultRadius,
                    color: '#FF8C00',
                    fillColor: '#FF8C00',
                    fillOpacity: 0.12,
                    weight: 2,
                }).addTo(this.map);

                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.lat = parseFloat(pos.lat.toFixed(7));
                    this.lng = parseFloat(pos.lng.toFixed(7));
                    this.circle.setLatLng(pos);
                });

                this.map.on('click', (e) => {
                    this.lat = parseFloat(e.latlng.lat.toFixed(7));
                    this.lng = parseFloat(e.latlng.lng.toFixed(7));
                    this.marker.setLatLng(e.latlng);
                    this.circle.setLatLng(e.latlng);
                });

                this.$watch('radius', (val) => {
                    if (this.circle && val) this.circle.setRadius(parseInt(val));
                });

                this.$watch('lat', (val) => {
                    if (this.marker && val && this.lng) {
                        const latlng = L.latLng(parseFloat(val), parseFloat(this.lng));
                        this.marker.setLatLng(latlng);
                        this.circle.setLatLng(latlng);
                        this.map.panTo(latlng);
                    }
                });

                this.$watch('lng', (val) => {
                    if (this.marker && val && this.lat) {
                        const latlng = L.latLng(parseFloat(this.lat), parseFloat(val));
                        this.marker.setLatLng(latlng);
                        this.circle.setLatLng(latlng);
                        this.map.panTo(latlng);
                    }
                });

                this.forceResize();
            },

            async init() {
                try {
                    await this.loadLeaflet();
                    this.loaded = true;

                    this.$nextTick(() => {
                        this.initMap();

                        // IntersectionObserver: initialize map when container becomes visible
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    this.initMap();
                                    this.forceResize();
                                }
                            });
                        }, { threshold: 0.1 });
                        observer.observe(this.$refs.map);

                        // MutationObserver: catch Filament section expand/collapse
                        const section = this.$el.closest('.fi-section');
                        if (section) {
                            new MutationObserver(() => {
                                setTimeout(() => {
                                    this.initMap();
                                    this.forceResize();
                                }, 200);
                            }).observe(section, { attributes: true, childList: true, subtree: true });
                        }

                        // Also watch parent tabs/steps
                        const tabPanel = this.$el.closest('[role=\"tabpanel\"], .fi-fo-tabs, .fi-fo-wizard-step');
                        if (tabPanel) {
                            new MutationObserver(() => {
                                setTimeout(() => {
                                    this.initMap();
                                    this.forceResize();
                                }, 300);
                            }).observe(tabPanel.parentElement, { attributes: true, childList: true, subtree: true });
                        }
                    });
                } catch (e) {
                    this.error = true;
                    console.error('Leaflet load error:', e);
                }
            }
        }"
        wire:ignore
        class="w-full"
    >
        {{-- Loading state --}}
        <template x-if="!loaded && !error">
            <div class="flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
                 style="height: 350px;">
                <div class="text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-orange-500 mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm text-gray-500">جاري تحميل الخريطة...</p>
                </div>
            </div>
        </template>

        {{-- Error state --}}
        <template x-if="error">
            <div class="flex items-center justify-center rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700"
                 style="height: 200px;">
                <div class="text-center text-red-600 dark:text-red-400">
                    <svg class="h-8 w-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <p class="text-sm font-medium">تعذّر تحميل الخريطة</p>
                    <p class="text-xs mt-1">تحقق من اتصال الإنترنت وأعد تحميل الصفحة</p>
                </div>
            </div>
        </template>

        {{-- Map container --}}
        <div
            x-ref="map"
            x-show="loaded && !error"
            x-transition
            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 shadow-sm"
            style="height: 350px; min-height: 250px; z-index: 1;"
        ></div>

        {{-- Refresh map button --}}
        <div x-show="loaded && !error" class="mt-2 flex items-center justify-center gap-3">
            <button type="button"
                    @click="forceResize()"
                    class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                تحديث الخريطة
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                📍 اضغط على الخريطة أو اسحب المؤشر لتحديد الموقع
            </p>
        </div>
    </div>
</x-dynamic-component>
