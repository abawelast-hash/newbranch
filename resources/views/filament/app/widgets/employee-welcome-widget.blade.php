{{-- SARH v1.9.0 — ويدجت الترحيب ببوابة الموظفين --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-4" dir="rtl">
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" style="font-family: 'Cairo', sans-serif;">
                    بوابة الموظفين — نظام سهر
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" style="font-family: 'Cairo', sans-serif;">
                    {{ $todayDate }}
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($checkedIn)
                        <x-filament::badge color="success" icon="heroicon-m-check-circle">
                            تم تسجيل الحضور ({{ $checkInTime }})
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="danger" icon="heroicon-m-x-circle">
                            لم يتم تسجيل الحضور بعد
                        </x-filament::badge>
                    @endif

                    @if ($checkedOut)
                        <x-filament::badge color="info" icon="heroicon-m-arrow-right-on-rectangle">
                            تم تسجيل الانصراف
                        </x-filament::badge>
                    @endif
                </div>
            </div>
            <div class="hidden sm:flex flex-col items-center">
                <x-heroicon-o-user-circle class="h-16 w-16 text-primary-500" />
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400" style="font-family: 'Cairo', sans-serif;">
                    {{ $userName }}
                </span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
