<?php

namespace Tests\Unit;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private StreakService $streakService;
    private User $user;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streakService = new StreakService();

        $this->branch = Branch::factory()->create([
            'weekend_days' => ['friday', 'saturday'],
        ]);

        $this->user = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
    }

    // ─── اختبارات السلسلة الأساسية ────────────────────────────────────────────

    /** @test */
    public function returns_zero_when_no_attendance(): void
    {
        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-04')); // الثلاثاء
        $this->assertEquals(0, $streak);
    }

    /** @test */
    public function counts_consecutive_present_days(): void
    {
        // 3 أيام حضور متواصلة: الأحد - الاثنين - الثلاثاء
        $this->makeAttendance('2026-02-01', 'present'); // الأحد
        $this->makeAttendance('2026-02-02', 'present'); // الاثنين
        $this->makeAttendance('2026-02-03', 'present'); // الثلاثاء

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-03'));
        $this->assertEquals(3, $streak);
    }

    /** @test */
    public function counts_late_attendance_as_part_of_streak(): void
    {
        $this->makeAttendance('2026-02-01', 'present');
        $this->makeAttendance('2026-02-02', 'late');    // متأخر يُحسب ✅
        $this->makeAttendance('2026-02-03', 'present');

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-03'));
        $this->assertEquals(3, $streak);
    }

    /** @test */
    public function breaks_streak_on_unexcused_absence(): void
    {
        $this->makeAttendance('2026-02-01', 'present');
        // 2026-02-02 غياب (لا سجل)
        $this->makeAttendance('2026-02-03', 'present');

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-03'));
        $this->assertEquals(1, $streak); // فقط اليوم الأخير
    }

    // ─── اختبارات تجاهل العطلة الأسبوعية ────────────────────────────────────

    /** @test */
    public function does_not_break_streak_across_weekend(): void
    {
        // الخميس 2026-02-05 + الأحد 2026-02-08 (عبر جمعة وسبت)
        $this->makeAttendance('2026-02-05', 'present'); // الخميس
        // الجمعة 2026-02-06 — إجازة أسبوعية
        // السبت 2026-02-07 — إجازة أسبوعية
        $this->makeAttendance('2026-02-08', 'present'); // الأحد ← يجب أن تستمر

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-08'));
        $this->assertEquals(2, $streak); // خميس + أحد
    }

    // ─── اختبارات تجاهل العطل الرسمية ───────────────────────────────────────

    /** @test */
    public function does_not_break_streak_on_official_holiday(): void
    {
        // إنشاء عطلة رسمية
        Holiday::create([
            'name_ar'   => 'اليوم الوطني',
            'name_en'   => 'National Day',
            'date'      => '2026-02-02',
            'type'      => 'official',
            'branch_id' => null, // تشمل الكل
        ]);

        $this->makeAttendance('2026-02-01', 'present');
        // 2026-02-02 عطلة رسمية — تُتجاهل
        $this->makeAttendance('2026-02-03', 'present');

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-03'));
        $this->assertEquals(2, $streak); // ✅ لم تُقطع بسبب العطلة
    }

    // ─── اختبارات تجاهل الإجازات المعتمدة ───────────────────────────────────

    /** @test */
    public function does_not_break_streak_on_approved_leave(): void
    {
        Leave::create([
            'user_id'    => $this->user->id,
            'branch_id'  => $this->branch->id,
            'leave_type' => 'annual',
            'start_date' => '2026-02-02',
            'end_date'   => '2026-02-04',
            'days_count' => 3,
            'status'     => 'approved',
            'reason'     => 'إجازة سنوية',
        ]);

        $this->makeAttendance('2026-02-01', 'present');
        // 2026-02-02 → 2026-02-04 إجازة معتمدة — تُتجاهل
        $this->makeAttendance('2026-02-05', 'present');

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-05'));
        $this->assertEquals(2, $streak); // ✅ إجازة لا تقطع السلسلة
    }

    /** @test */
    public function pending_leave_does_break_streak(): void
    {
        Leave::create([
            'user_id'    => $this->user->id,
            'branch_id'  => $this->branch->id,
            'leave_type' => 'annual',
            'start_date' => '2026-02-02',
            'end_date'   => '2026-02-02',
            'days_count' => 1,
            'status'     => 'pending', // ← غير معتمدة
            'reason'     => 'طلب إجازة',
        ]);

        $this->makeAttendance('2026-02-01', 'present');
        // 2026-02-02 غياب (إجازة pending لا تُعتد)
        $this->makeAttendance('2026-02-03', 'present');

        $streak = $this->streakService->calculate($this->user, Carbon::parse('2026-02-03'));
        $this->assertEquals(1, $streak); // ❌ قُطعت السلسلة
    }

    // ─── اختبار isWeekend ────────────────────────────────────────────────────

    /** @test */
    public function correctly_identifies_weekends(): void
    {
        $this->assertTrue($this->streakService->isWeekend(Carbon::parse('2026-02-06'))); // جمعة
        $this->assertTrue($this->streakService->isWeekend(Carbon::parse('2026-02-07'))); // سبت
        $this->assertFalse($this->streakService->isWeekend(Carbon::parse('2026-02-08'))); // أحد
        $this->assertFalse($this->streakService->isWeekend(Carbon::parse('2026-02-09'))); // اثنين
    }

    // ─── اختبار isNonWorkingDay ───────────────────────────────────────────────

    /** @test */
    public function identifies_non_working_days_correctly(): void
    {
        Holiday::create([
            'name_ar'   => 'يوم المؤسس',
            'name_en'   => 'Founder Day',
            'date'      => '2026-02-23',
            'type'      => 'official',
            'branch_id' => null,
        ]);

        $this->assertTrue($this->streakService->isNonWorkingDay(Carbon::parse('2026-02-23'))); // عطلة
        $this->assertTrue($this->streakService->isNonWorkingDay(Carbon::parse('2026-02-28'))); // سبت
        $this->assertFalse($this->streakService->isNonWorkingDay(Carbon::parse('2026-02-25'))); // أربعاء عادي
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function makeAttendance(string $date, string $status): void
    {
        AttendanceLog::factory()->create([
            'user_id'         => $this->user->id,
            'branch_id'       => $this->branch->id,
            'attendance_date' => $date,
            'status'          => $status,
        ]);
    }
}
