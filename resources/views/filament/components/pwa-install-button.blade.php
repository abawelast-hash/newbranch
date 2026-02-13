{{-- SARH v2.0 — PWA Install Button (beforeinstallprompt) --}}
<div
    x-data="{
        showInstall: false,
        deferredPrompt: null,
        init() {
            // Register Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            }

            // Capture the beforeinstallprompt event
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.showInstall = true;
            });

            // Hide once installed
            window.addEventListener('appinstalled', () => {
                this.showInstall = false;
                this.deferredPrompt = null;
            });
        },
        async install() {
            if (!this.deferredPrompt) return;
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            this.deferredPrompt = null;
            this.showInstall = false;
        }
    }"
    x-show="showInstall"
    x-transition
    x-cloak
    class="flex items-center"
>
    <button
        @click="install()"
        class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors duration-150"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
        </svg>
        <span>ثبّت التطبيق</span>
    </button>
</div>
