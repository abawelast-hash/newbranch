<?php

use App\Http\Controllers\AttendanceController;
use App\Livewire\EmployeeDashboard;
use App\Livewire\MessagingChat;
use App\Livewire\MessagingInbox;
use App\Livewire\WhistleblowerForm;
use App\Livewire\WhistleblowerTrack;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

/*
|--------------------------------------------------------------------------
| PWA Manifest (Dynamic from Settings)
|--------------------------------------------------------------------------
*/
Route::get('/manifest.json', function () {
    $s = Setting::instance();
    $iconUrl = $s->logo_url ?? '/icon-192.png';

    return response()->json([
        'name'             => $s->pwa_name ?? 'سهر - SARH',
        'short_name'       => $s->pwa_short_name ?? 'سهر',
        'description'      => $s->welcome_body ?? 'نظام إدارة الموارد البشرية الذكي',
        'start_url'        => '/app/login',
        'scope'            => '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'theme_color'      => $s->pwa_theme_color ?? '#F97316',
        'background_color' => $s->pwa_background_color ?? '#F0F2F5',
        'lang'             => 'ar',
        'dir'              => 'rtl',
        'categories'       => ['business', 'productivity'],
        'icons'            => [
            ['src' => $iconUrl, 'sizes' => '72x72',   'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '96x96',   'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '384x384', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ['src' => $iconUrl, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('manifest');

/*
|--------------------------------------------------------------------------
| Service Worker (PWA)
|--------------------------------------------------------------------------
*/
Route::get('/sw.js', function () {
    return response(
        <<<'JS'
        const CACHE_NAME = 'sarh-v2';
        const OFFLINE_URL = '/offline';
        const STATIC_ASSETS = [
            '/manifest.json',
        ];

        // Install — pre-cache critical assets
        self.addEventListener('install', (e) => {
            e.waitUntil(
                caches.open(CACHE_NAME).then((cache) => {
                    return cache.addAll(STATIC_ASSETS);
                }).then(() => self.skipWaiting())
            );
        });

        // Activate — clean old caches
        self.addEventListener('activate', (e) => {
            e.waitUntil(
                caches.keys().then((names) => {
                    return Promise.all(
                        names.filter(n => n !== CACHE_NAME).map(n => caches.delete(n))
                    );
                }).then(() => self.clients.claim())
            );
        });

        // Fetch — network-first for navigations, cache-first for assets
        self.addEventListener('fetch', (e) => {
            const url = new URL(e.request.url);

            // Skip non-GET and external requests
            if (e.request.method !== 'GET' || url.origin !== location.origin) return;

            // CSS/JS/images: cache-first
            if (url.pathname.startsWith('/build/') || url.pathname.match(/\.(css|js|png|jpg|svg|woff2?)$/)) {
                e.respondWith(
                    caches.match(e.request).then((cached) => {
                        if (cached) return cached;
                        return fetch(e.request).then((resp) => {
                            if (resp.ok) {
                                const clone = resp.clone();
                                caches.open(CACHE_NAME).then((c) => c.put(e.request, clone));
                            }
                            return resp;
                        });
                    })
                );
                return;
            }

            // Navigation: network-first, fallback to cache
            if (e.request.mode === 'navigate') {
                e.respondWith(
                    fetch(e.request).catch(() => caches.match(e.request))
                );
                return;
            }
        });
        JS,
        200,
        ['Content-Type' => 'application/javascript', 'Service-Worker-Allowed' => '/']
    );
})->name('sw');

/*
|--------------------------------------------------------------------------
| Employee PWA Routes (Authenticated)
|--------------------------------------------------------------------------
| Main dashboard and authenticated features.
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', EmployeeDashboard::class)->name('dashboard');
    Route::get('/messaging', MessagingInbox::class)->name('messaging.inbox');
    Route::get('/messaging/{conversation}', MessagingChat::class)->name('messaging.chat');
});

/*
|--------------------------------------------------------------------------
| Whistleblower Routes (NO Authentication — Anonymous)
|--------------------------------------------------------------------------
| These routes must remain public. No auth, no IP logging, no sessions tracking.
*/
Route::get('/whistleblower', WhistleblowerForm::class)->name('whistleblower.form');
Route::get('/whistleblower/track', WhistleblowerTrack::class)->name('whistleblower.track');

/*
|--------------------------------------------------------------------------
| Attendance API Routes (PWA — Authenticated)
|--------------------------------------------------------------------------
| These routes serve the PWA check-in/check-out flow.
| They require authentication (session-based or Sanctum).
*/
Route::middleware(['auth'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::post('/check-in',  [AttendanceController::class, 'checkIn'])->name('check_in');
    Route::post('/queue-check-in', [AttendanceController::class, 'queueCheckIn'])->name('queue_check_in');
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check_out');
    Route::get('/today',      [AttendanceController::class, 'todayStatus'])->name('today');
});

/*
|--------------------------------------------------------------------------
| Telemetry Routes (Sensor Productivity — Authenticated)
|--------------------------------------------------------------------------
| Receives edge-processed sensor data from mobile app.
| Sends configuration back to the app.
*/
Route::middleware(['auth'])->prefix('telemetry')->name('telemetry.')->group(function () {
    Route::post('/push',   [\App\Http\Controllers\TelemetryController::class, 'push'])->name('push');
    Route::get('/config',  [\App\Http\Controllers\TelemetryController::class, 'config'])->name('config');
});
