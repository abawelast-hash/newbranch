@props([
    'type' => 'default',
    'size' => 'md',
    'icon' => null,
])

@php
$colors = [
    'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
    'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    'orange'  => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-3 py-1 text-sm',
    'lg' => 'px-4 py-1.5 text-base',
];

$baseClasses = 'inline-flex items-center font-medium rounded-full';
$colorClasses = $colors[$type] ?? $colors['default'];
$sizeClasses = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $colorClasses $sizeClasses"]) }}>
    @if($icon)
        <x-dynamic-component :component="$icon" class="w-4 h-4 ltr:mr-1 rtl:ml-1" />
    @endif
    {{ $slot }}
</span>
