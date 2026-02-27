<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class StreakService
{
    /**
     * احتساب سلسلة الحضور المتواصلة للموظف.
     *
     * القواعد:
     * - يتجاهل أيام الجمعة والسبت (عطلة أسبوعية سعودية)
     * - يتجاهل العطل الرسمية (Holiday model)
     * - يتجاهل الإجازات المعتمدة (Leave model) ولا تقطع السلسلة
     * - يحتسب الحضور المتأخر (late) ضمن السلسلة
     * - يقطع السلسلة عند أول يوم عمل غائب فيه بدون مبرر
     *
     * @param  User        $user   الموظف
     * @param  Carbon|null $from   تاريخ البداية (اليوم افتراضياً)
     * @param  int         $maxDays الحد الأقصى للأيام المفحوصة (حماية من الحلقات اللانهائية)
     */
    public function calculate(User $user, ?Carbon $from = null, int $maxDays = 365): int
    {
        $current  = ($from ?? now())->copy()->startOfDay();
        $streak   = 0;
        $checked  = 0;
        $branchId = $user->branch_id;

        // جلب كل سجلات الحضور المحتملة دفعة واحدة (تجنب N+1)
        $from365  = $current->copy()->subDays($maxDays);
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$from365->toDateString(), $current->toDateString()])
            ->whereIn('status', ['present', 'late'])
            ->pluck('attendance_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip() // key = date للبحث السريع O(1)
            ->all();

        // جلب إجازات الموظف دفعة واحدة
        $approvedLeaves = Leave::where('user_id', $user->id)
            ->approved()
            ->where('end_date', '>=', $from365->toDateString())
            ->get(['start_date', 'end_date']);

        // جلب العطل الرسمية دفعة واحدة
        $holidays = Holiday::where('date', '>=', $from365->toDateString())
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            })
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        while ($checked < $maxDays) {
            $dateStr = $current->toDateString();
            $checked++;

            // ─── تجاوز عطلة الأسبوع ──────────────────────────────────────────
            if ($this->isWeekend($current)) {
                $current->subDay();
                continue;
            }

            // ─── تجاوز العطل الرسمية (لا تقطع السلسلة) ──────────────────────
            if (isset($holidays[$dateStr])) {
                $current->subDay();
                continue;
            }

            // ─── تجاوز الإجازات المعتمدة (لا تقطع السلسلة) ─────────────────
            if ($this->isOnApprovedLeave($approvedLeaves, $current)) {
                $current->subDay();
                continue;
            }

            // ─── يوم عمل فعلي: هل حضر؟ ──────────────────────────────────────
            if (isset($logs[$dateStr])) {
                $streak++;
                $current->subDay();
            } else {
                // غائب بدون مبرر — قطع السلسلة
                break;
            }
        }

        return $streak;
    }

    /**
     * نسخة مع Cache لتخفيف الاستعلامات في الـ widgets
     */
    public function calculateCached(User $user, ?Carbon $from = null, int $ttlMinutes = 10): int
    {
        $date = ($from ?? now())->toDateString();
        $key  = "streak.{$user->id}.{$date}";

        return Cache::remember($key, now()->addMinutes($ttlMinutes), fn () => $this->calculate($user, $from));
    }

    /**
     * إبطال cache عند تسجيل حضور جديد
     */
    public function invalidate(User $user): void
    {
        Cache::forget("streak.{$user->id}." . now()->toDateString());
    }

    // ─── دوال مساعدة عامة (يستخدمها AnalyticsService أيضاً) ─────────────────

    public function isHoliday(Carbon $date, ?int $branchId = null): bool
    {
        return Holiday::where('date', $date->toDateString())
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id');
                if ($branchId) {
                    $q->orWhere('branch_id', $branchId);
                }
            })
            ->exists();
    }

    public function isEmployeeOnLeave(User $user, Carbon $date): bool
    {
        return Leave::where('user_id', $user->id)
            ->approved()
            ->onDate($date)
            ->exists();
    }

    public function isWeekend(Carbon $date): bool
    {
        // الجمعة = 5، السبت = 6 (عطلة سعودية)
        return in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY]);
    }

    public function isNonWorkingDay(Carbon $date, ?int $branchId = null): bool
    {
        return $this->isWeekend($date) || $this->isHoliday($date, $branchId);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function isOnApprovedLeave($leaves, Carbon $date): bool
    {
        $dateStr = $date->toDateString();

        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date)->toDateString();
            $end   = Carbon::parse($leave->end_date)->toDateString();

            if ($dateStr >= $start && $dateStr <= $end) {
                return true;
            }
        }

        return false;
    }
}
