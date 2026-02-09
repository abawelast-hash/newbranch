<?php

namespace App\Filament\App\Widgets;

use App\Models\AttendanceLog;
use Filament\Widgets\Widget;

/**
 * SARH v1.9.0 — ويدجت الترحيب بالموظف في لوحة /app
 *
 * يعرض: اسم الموظف، تاريخ اليوم، حالة الحضور اليومية.
 */
class EmployeeWelcomeWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.employee-welcome-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public function getViewData(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // فحص: هل سجّل الموظف حضوره اليوم؟
        $todayLog = AttendanceLog::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', today())
            ->first();

        $checkedIn = (bool) $todayLog?->check_in_at;
        $checkedOut = (bool) $todayLog?->check_out_at;

        return [
            'userName'    => $user->name_ar ?? $user->name ?? 'موظف',
            'todayDate'   => now()->translatedFormat('l، j F Y'),
            'checkedIn'   => $checkedIn,
            'checkedOut'  => $checkedOut,
            'checkInTime' => $todayLog?->check_in_at?->format('H:i'),
        ];
    }
}
