<?php

namespace App\Filament\Pages;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\FinancialReport;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * SARH — بيانات النشر
 *
 * صفحة مرجعية تعرض حالة النظام للنشر:
 * - الفروع الحالية وإحداثياتها
 * - الموظفون وبياناتهم
 * - إعادة تصفير جميع السجلات
 * - كلمة مرور افتراضية 123456
 * - مناوبة واحدة 08:00—21:00 عدا الجمعة
 *
 * ⚠️ متاحة فقط لـ super_admin / Level 10
 */
class DeploymentDataPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationGroup = 'أدوات المطور';

    protected static ?int $navigationSort = 98;

    protected static ?string $title = 'بيانات النشر';

    protected static ?string $navigationLabel = 'بيانات النشر';

    protected static ?string $slug = 'deployment-data';

    protected static string $view = 'filament.pages.deployment-data';

    public array $branches = [];
    public array $employees = [];
    public array $shiftInfo = [];
    public array $stats = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || ($user->security_level ?? 0) >= 10);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        // Branches with coordinates
        $this->branches = Branch::withCount('users')
            ->orderBy('name_ar')
            ->get()
            ->map(fn ($b) => [
                'id'         => $b->id,
                'name'       => $b->name_ar ?: $b->name_en,
                'code'       => $b->code,
                'city'       => $b->city_ar ?: $b->city_en,
                'latitude'   => $b->latitude,
                'longitude'  => $b->longitude,
                'radius'     => $b->geofence_radius,
                'shift_start'=> $b->default_shift_start ?? '08:00',
                'shift_end'  => $b->default_shift_end ?? '21:00',
                'is_active'  => $b->is_active,
                'employees'  => $b->users_count,
            ])
            ->toArray();

        // Employees with emails and branch
        $this->employees = User::with('branch')
            ->where('is_super_admin', false)
            ->orderBy('branch_id')
            ->orderBy('name_ar')
            ->get()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'employee_id' => $u->employee_id,
                'name'        => $u->name_ar ?: $u->name_en,
                'email'       => $u->email,
                'phone'       => $u->phone,
                'branch'      => $u->branch?->name_ar ?: $u->branch?->name_en ?? '—',
                'branch_id'   => $u->branch_id,
                'job_title'   => $u->job_title_ar ?: $u->job_title_en ?? '—',
                'status'      => $u->status,
                'has_avatar'  => !empty($u->avatar),
            ])
            ->toArray();

        // Shift info
        $standardShift = Shift::where('is_active', true)->first();
        $this->shiftInfo = [
            'name'       => $standardShift?->name_ar ?? 'مناوبة رئيسية',
            'start'      => $standardShift?->start_time ?? '08:00',
            'end'        => $standardShift?->end_time ?? '21:00',
            'exists'     => (bool) $standardShift,
            'total'      => Shift::count(),
        ];

        // Stats summary
        $this->stats = [
            'total_branches'  => Branch::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'total_employees' => User::where('is_super_admin', false)->count(),
            'active_employees'=> User::where('is_super_admin', false)->where('status', 'active')->count(),
            'attendance_logs' => AttendanceLog::count(),
            'leave_requests'  => LeaveRequest::count(),
            'payrolls'        => Payroll::count(),
            'financial_reports'=> FinancialReport::count(),
        ];
    }

    /**
     * تصفير جميع السجلات التشغيلية (الحضور، الإجازات، المالية، الرواتب).
     */
    public function resetAllRecords(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        AttendanceLog::truncate();
        LeaveRequest::truncate();

        if (class_exists(\App\Models\Payroll::class)) {
            Payroll::truncate();
        }
        if (class_exists(\App\Models\FinancialReport::class)) {
            FinancialReport::truncate();
        }
        if (class_exists(\App\Models\LossAlert::class)) {
            \App\Models\LossAlert::truncate();
        }
        if (class_exists(\App\Models\AnalyticsSnapshot::class)) {
            \App\Models\AnalyticsSnapshot::truncate();
        }
        if (class_exists(\App\Models\EmployeePattern::class)) {
            \App\Models\EmployeePattern::truncate();
        }
        if (class_exists(\App\Models\PointsTransaction::class)) {
            \App\Models\PointsTransaction::truncate();
        }
        if (class_exists(\App\Models\ScoreAdjustment::class)) {
            \App\Models\ScoreAdjustment::truncate();
        }

        // Reset employee counters
        User::where('is_super_admin', false)->update([
            'total_points'   => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        // Reset branch financial counters
        Branch::query()->update([
            'monthly_delay_losses' => 0,
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->loadData();

        Notification::make()
            ->title('تم تصفير جميع السجلات')
            ->body('تم حذف سجلات الحضور والإجازات والرواتب والتقارير المالية والتحليلات.')
            ->success()
            ->send();
    }

    /**
     * إعادة تعيين كلمات المرور لجميع الموظفين إلى 123456.
     */
    public function resetAllPasswords(): void
    {
        User::where('is_super_admin', false)->update([
            'password' => Hash::make('123456'),
        ]);

        $this->loadData();

        Notification::make()
            ->title('تم إعادة تعيين كلمات المرور')
            ->body('كلمة المرور الافتراضية لجميع الموظفين: 123456')
            ->success()
            ->send();
    }

    /**
     * تعيين صورة الشعار كصورة بروفايل لجميع الموظفين.
     */
    public function setLogoAsAvatar(): void
    {
        $setting = Setting::instance();
        $logoPath = $setting->logo_path;

        if (!$logoPath) {
            Notification::make()
                ->title('لا يوجد شعار')
                ->body('يرجى رفع شعار في الإعدادات أولاً.')
                ->danger()
                ->send();
            return;
        }

        User::where('is_super_admin', false)->update([
            'avatar' => $logoPath,
        ]);

        $this->loadData();

        Notification::make()
            ->title('تم تعيين صورة الشعار')
            ->body('تم تعيين صورة الشعار كصورة بروفايل لجميع الموظفين.')
            ->success()
            ->send();
    }

    /**
     * إنشاء أو تحديث مناوبة واحدة 08:00—21:00 وربطها بجميع الموظفين (عدا الجمعة).
     */
    public function applyStandardShift(): void
    {
        // Create or update the standard shift
        $shift = Shift::updateOrCreate(
            ['name_ar' => 'الدوام الرسمي'],
            [
                'name_en'              => 'Standard Shift',
                'start_time'           => '08:00',
                'end_time'             => '21:00',
                'grace_period_minutes' => 15,
                'is_overnight'         => false,
                'is_active'            => true,
            ]
        );

        // Assign to all non-admin employees
        $employeeIds = User::where('is_super_admin', false)
            ->pluck('id')
            ->toArray();

        // Detach old shifts and attach the standard one
        foreach ($employeeIds as $userId) {
            DB::table('user_shifts')
                ->where('user_id', $userId)
                ->update(['is_current' => false]);

            DB::table('user_shifts')->updateOrInsert(
                ['user_id' => $userId, 'shift_id' => $shift->id],
                [
                    'is_current'     => true,
                    'effective_from' => now()->startOfMonth()->toDateString(),
                    'effective_to'   => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]
            );
        }

        // Update branch default shifts
        Branch::query()->update([
            'default_shift_start' => '08:00',
            'default_shift_end'   => '21:00',
        ]);

        $this->loadData();

        Notification::make()
            ->title('تم تطبيق المناوبة الموحدة')
            ->body("مناوبة واحدة 08:00—21:00 (عدا الجمعة) مرتبطة بـ {$shift->users()->count()} موظف.")
            ->success()
            ->send();
    }

    /**
     * تنفيذ جميع عمليات التهيئة دفعة واحدة.
     */
    public function runFullDeploymentReset(): void
    {
        $this->resetAllRecords();
        $this->resetAllPasswords();
        $this->setLogoAsAvatar();
        $this->applyStandardShift();

        Notification::make()
            ->title('✅ تم تهيئة النظام للنشر')
            ->body('تصفير السجلات + كلمات المرور 123456 + صورة الشعار + مناوبة 08:00-21:00')
            ->success()
            ->duration(8000)
            ->send();
    }
}
