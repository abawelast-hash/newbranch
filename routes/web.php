<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TrapController;
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
    $iconUrl = $s->logo_url ?? '/build/assets/icon-512.png';

    return response()->json([
        'name'             => $s->pwa_name,
        'short_name'       => $s->pwa_short_name,
        'description'      => $s->welcome_body ?? 'نظام إدارة الموارد البشرية',
        'start_url'        => '/app/login',
        'scope'            => '/',
        'display'          => 'standalone',
        'orientation'      => 'portrait',
        'theme_color'      => $s->pwa_theme_color,
        'background_color' => $s->pwa_background_color,
        'lang'             => 'ar',
        'dir'              => 'rtl',
        'icons'            => [
            ['src' => $iconUrl, 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $iconUrl, 'sizes' => '512x512', 'type' => 'image/png'],
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
        self.addEventListener('install', (e) => self.skipWaiting());
        self.addEventListener('activate', (e) => e.waitUntil(clients.claim()));
        self.addEventListener('fetch', (e) => {
            if (e.request.mode === 'navigate') {
                e.respondWith(fetch(e.request).catch(() => caches.match('/offline.html')));
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
    Route::post('/check-out', [AttendanceController::class, 'checkOut'])->name('check_out');
    Route::get('/today',      [AttendanceController::class, 'todayStatus'])->name('today');
});

/*
|--------------------------------------------------------------------------
| Trap System Routes (PWA — Authenticated)
|--------------------------------------------------------------------------
| Trap trigger endpoint. The PWA sends trap interactions here.
| The response is a convincing fake payload — no real data exposed.
*/
Route::middleware(['auth'])->prefix('traps')->name('traps.')->group(function () {
    Route::post('/trigger', [TrapController::class, 'trigger'])->name('trigger');
});
