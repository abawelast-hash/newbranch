<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('pwa.app_title') }} — {{ $title ?? __('pwa.dashboard') }}</title>

    <!-- Tajawal Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased min-h-screen">

    <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

        {{-- Sidebar Overlay (Mobile) --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-50 lg:hidden"
             @click="sidebarOpen = false">
        </div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : (document.dir === 'rtl' ? 'translate-x-full' : '-translate-x-full')"
               class="fixed inset-y-0 z-50 w-72 bg-white border-e border-gray-200 shadow-lg transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
               :style="document.dir === 'rtl' ? 'right: 0' : 'left: 0'">

            {{-- Logo --}}
            <div class="flex items-center gap-3 h-16 px-6 border-b border-gray-100">
                <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">{{ __('pwa.app_name') }}</h1>
                    <p class="text-xs text-gray-400">{{ __('pwa.app_subtitle') }}</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    {{ __('pwa.nav_dashboard') }}
                </a>

                <a href="{{ route('messaging.inbox') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('messaging.*') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    {{ __('pwa.nav_messages') }}
                </a>

                <a href="{{ route('whistleblower.form') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('whistleblower.*') ? 'bg-orange-50 text-orange-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    {{ __('pwa.nav_whistleblower') }}
                </a>

            </nav>

            {{-- User Info (Bottom) --}}
            @auth
            <div class="absolute bottom-0 w-full p-4 border-t border-gray-100 bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <span class="text-sm font-bold text-orange-700">{{ mb_substr(auth()->user()->name_ar ?? 'U', 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ auth()->user()->name_ar }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->job_title_ar ?? auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
            @endauth
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white border-b border-gray-100 h-16 flex items-center px-6 gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <h2 class="text-lg font-bold text-gray-800">{{ $title ?? __('pwa.dashboard') }}</h2>

                <div class="flex-1"></div>

                {{-- Language Toggle --}}
                <a href="?lang={{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}" class="text-sm text-gray-500 hover:text-orange-600 font-medium">
                    {{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}
                </a>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-6">
                {{ $slot }}
            </main>

        </div>
    </div>

    @livewireScripts
</body>
</html>
