<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * SARH v1.9.0 — الصفحة الرئيسية لبوابة الموظفين
 *
 * تعرض لوحة معلومات مخصصة للموظف مع تحية حسب الوقت.
 */
class EmployeeDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'الرئيسية';

    protected static ?string $navigationLabel = 'الرئيسية';

    protected static ?int $navigationSort = -2;

    protected static string $routePath = '/';

    public function getHeading(): string
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $name = $user?->name_ar ?? $user?->name ?? 'موظف';
        $hour = (int) now()->format('H');

        $greeting = match (true) {
            $hour >= 5 && $hour < 12   => 'صباح الخير',
            $hour >= 12 && $hour < 17  => 'مساء الخير',
            $hour >= 17 && $hour < 21  => 'مساء النور',
            default                     => 'أهلاً',
        };

        return "{$greeting}، {$name}";
    }

    public function getSubheading(): ?string
    {
        return 'مرحباً بك في بوابة الموظفين — نظام سهر لإدارة الموارد البشرية';
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\App\Widgets\EmployeeWelcomeWidget::class,
        ];
    }
}
