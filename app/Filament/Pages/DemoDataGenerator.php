<?php

namespace App\Filament\Pages;

use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SARH v1.9.0 — مولّد البيانات التجريبية
 *
 * صفحة إدارية لتوليد بيانات حضور واقعية للعرض على أصحاب المصلحة.
 * تستخدم مقياس "الانضباط" (1-10) للتحكم في مستوى فوضوية البيانات.
 *
 * ⚠️ متاحة فقط لـ Level 10 / super_admin
 */
class DemoDataGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'أدوات المطور';

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'مولّد البيانات التجريبية';

    protected static ?string $navigationLabel = 'مولّد البيانات';

    protected static ?string $slug = 'demo-data-generator';

    protected static string $view = 'filament.pages.demo-data-generator';

    // ── Form State ──────────────────────────────────────────
    public ?array $data = [];

    // ── Preview Results ─────────────────────────────────────
    public array $previewStats = [];
    public bool $showPreview = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->is_super_admin || $user->security_level >= 10);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill([
            'date_from'        => now()->startOfMonth()->format('Y-m-d'),
            'date_to'          => now()->format('Y-m-d'),
            'branch_ids'       => [],
            'compliance_gauge' => 7,
            'shift_start'      => '08:00',
            'shift_end'        => '17:00',
            'weekend_days'     => [5, 6], // Friday, Saturday
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Section 1: Date Range ─────────────────────────────
                Forms\Components\Section::make('نطاق التاريخ')
                    ->icon('heroicon-o-calendar-days')
                    ->description('حدد الفترة الزمنية التي سيتم توليد بيانات الحضور لها')
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('من تاريخ')
                            ->required()
                            ->native(false)
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'أول يوم سيتم توليد بيانات حضور له'),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('إلى تاريخ')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('date_from')
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'آخر يوم سيتم توليد بيانات حضور له'),
                    ])->columns(['default' => 1, 'lg' => 2]),

                // ── Section 2: Branch Selector ────────────────────────
                Forms\Components\Section::make('اختيار الفروع')
                    ->icon('heroicon-o-building-office-2')
                    ->description('اختر الفروع التي سيتم توليد البيانات لها — أو اتركها فارغة لجميع الفروع')
                    ->schema([
                        Forms\Components\CheckboxList::make('branch_ids')
                            ->label('الفروع')
                            ->options(Branch::where('is_active', true)->pluck('name_ar', 'id'))
                            ->columns(['default' => 1, 'lg' => 3])
                            ->bulkToggleable()
                            ->searchable()
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'اتركها فارغة لتشمل جميع الفروع النشطة'),
                    ]),

                // ── Section 3: Shift Settings ─────────────────────────
                Forms\Components\Section::make('إعدادات الدوام')
                    ->icon('heroicon-o-clock')
                    ->description('ساعات الدوام المرجعية — إذا تُركت فارغة يُستخدم دوام كل فرع')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TimePicker::make('shift_start')
                            ->label('بداية الدوام')
                            ->seconds(false)
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الرسمي لبداية الدوام — يُستخدم لحساب التأخير'),

                        Forms\Components\TimePicker::make('shift_end')
                            ->label('نهاية الدوام')
                            ->seconds(false)
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'الوقت الرسمي لنهاية الدوام — يُستخدم لحساب ساعات العمل'),
                    ])->columns(['default' => 1, 'lg' => 2]),

                // ── Section 4: Weekend Selector ───────────────────────
                Forms\Components\Section::make('أيام العطلة')
                    ->icon('heroicon-o-calendar')
                    ->description('الأيام المستبعدة من التوليد')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\CheckboxList::make('weekend_days')
                            ->label('أيام الإجازة الأسبوعية')
                            ->options([
                                0 => 'الأحد',
                                1 => 'الاثنين',
                                2 => 'الثلاثاء',
                                3 => 'الأربعاء',
                                4 => 'الخميس',
                                5 => 'الجمعة',
                                6 => 'السبت',
                            ])
                            ->columns(['default' => 3, 'lg' => 7])
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'لن يتم توليد سجلات حضور لهذه الأيام'),
                    ]),

                // ── Section 5: Compliance Gauge ───────────────────────
                Forms\Components\Section::make('مقياس الانضباط')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description('يتحكم في واقعية البيانات — 10 = حضور مثالي، 1 = فوضى عالية')
                    ->schema([
                        Forms\Components\TextInput::make('compliance_gauge')
                            ->label('مستوى الانضباط')
                            ->type('range')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->step(1)
                            ->required()
                            ->extraAttributes(['min' => 1, 'max' => 10, 'class' => 'w-full'])
                            ->helperText(fn (Forms\Get $get): string => match ((int) ($get('compliance_gauge') ?? 7)) {
                                10      => '🟢 مستوى 10: حضور مثالي — الجميع في الوقت تماماً',
                                9       => '🟢 مستوى 9: ممتاز — تأخيرات نادرة جداً (1-3 دقائق)',
                                8       => '🟢 مستوى 8: جيد جداً — تأخيرات خفيفة عرضية',
                                7       => '🟡 مستوى 7: جيد — تأخيرات بسيطة (5-15 دقيقة)',
                                6       => '🟡 مستوى 6: مقبول — بعض التأخيرات والغيابات',
                                5       => '🟠 مستوى 5: متوسط — تأخيرات متكررة وغيابات عرضية',
                                4       => '🟠 مستوى 4: ضعيف — نسبة تأخير عالية',
                                3       => '🔴 مستوى 3: سيء — غيابات كثيرة وتأخيرات طويلة',
                                2       => '🔴 مستوى 2: سيء جداً — فوضى شبه كاملة',
                                1       => '🔴 مستوى 1: كارثي — بيانات عشوائية تماماً',
                                default => 'حرّك المؤشر لاختيار المستوى',
                            })
                            ->live()
                            ->hintIcon('heroicon-m-information-circle', tooltip: 'مقياس الانضباط يتحكم في نسبة التأخيرات والغيابات والانصراف المبكر في البيانات المولّدة'),
                    ]),
            ])
            ->statePath('data');
    }

    // ═══════════════════════════════════════════════════════════
    //  ACTION: Generate Preview
    // ═══════════════════════════════════════════════════════════
    public function generatePreview(): void
    {
        $data = $this->form->getState();
        $stats = $this->calculateGenerationStats($data);

        $this->previewStats = $stats;
        $this->showPreview  = true;

        Notification::make()
            ->title('تم حساب المعاينة')
            ->body("سيتم توليد {$stats['total_records']} سجل حضور لـ {$stats['total_users']} موظف في {$stats['working_days']} يوم عمل.")
            ->icon('heroicon-o-eye')
            ->color('info')
            ->send();
    }

    // ═══════════════════════════════════════════════════════════
    //  ACTION: Commit — Insert Records
    // ═══════════════════════════════════════════════════════════
    public function commitRecords(): void
    {
        $data = $this->form->getState();

        $branches  = $this->resolveBranches($data);
        $dateFrom  = Carbon::parse($data['date_from']);
        $dateTo    = Carbon::parse($data['date_to']);
        $gauge     = (int) $data['compliance_gauge'];
        $weekends  = array_map('intval', $data['weekend_days'] ?? [5, 6]);
        $shiftStartGlobal = $data['shift_start'] ?? null;
        $shiftEndGlobal   = $data['shift_end'] ?? null;

        $totalInserted = 0;
        $batchSize = 200;
        $batch     = [];

        foreach ($branches as $branch) {
            $users = User::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->get();

            if ($users->isEmpty()) continue;

            $shiftStart = $shiftStartGlobal ?: $branch->default_shift_start;
            $shiftEnd   = $shiftEndGlobal ?: $branch->default_shift_end;
            $graceMinutes = $branch->grace_period_minutes ?? 5;

            $period = CarbonPeriod::create($dateFrom, $dateTo);

            foreach ($period as $day) {
                // Skip weekends
                if (in_array($day->dayOfWeek, $weekends)) continue;

                foreach ($users as $user) {
                    // Skip if record already exists
                    $exists = AttendanceLog::where('user_id', $user->id)
                        ->where('attendance_date', $day->format('Y-m-d'))
                        ->exists();
                    if ($exists) continue;

                    $record = $this->generateAttendanceRecord(
                        $user, $branch, $day, $shiftStart, $shiftEnd, $graceMinutes, $gauge
                    );

                    $batch[] = $record;

                    if (count($batch) >= $batchSize) {
                        AttendanceLog::insert($batch);
                        $totalInserted += count($batch);
                        $batch = [];
                    }
                }
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            AttendanceLog::insert($batch);
            $totalInserted += count($batch);
        }

        $this->showPreview = false;

        Notification::make()
            ->title('تم توليد البيانات بنجاح ✅')
            ->body("تم إدراج {$totalInserted} سجل حضور تجريبي.")
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->persistent()
            ->send();
    }

    // ═══════════════════════════════════════════════════════════
    //  ACTION: Wipe/Reset — Truncate for range/branch
    // ═══════════════════════════════════════════════════════════
    public function wipeRecords(): void
    {
        $data = $this->form->getState();

        $branches = $this->resolveBranches($data);
        $branchIds = $branches->pluck('id')->toArray();

        $dateFrom = Carbon::parse($data['date_from'])->format('Y-m-d');
        $dateTo   = Carbon::parse($data['date_to'])->format('Y-m-d');

        $deleted = AttendanceLog::whereIn('branch_id', $branchIds)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->delete();

        $this->showPreview = false;

        Notification::make()
            ->title('تم المسح ⚠️')
            ->body("تم حذف {$deleted} سجل حضور في الفترة المحددة.")
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->persistent()
            ->send();
    }

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE: Calculate Preview Stats
    // ═══════════════════════════════════════════════════════════
    protected function calculateGenerationStats(array $data): array
    {
        $branches  = $this->resolveBranches($data);
        $dateFrom  = Carbon::parse($data['date_from']);
        $dateTo    = Carbon::parse($data['date_to']);
        $weekends  = array_map('intval', $data['weekend_days'] ?? [5, 6]);
        $gauge     = (int) $data['compliance_gauge'];

        // Count working days
        $workingDays = 0;
        $period = CarbonPeriod::create($dateFrom, $dateTo);
        foreach ($period as $day) {
            if (!in_array($day->dayOfWeek, $weekends)) {
                $workingDays++;
            }
        }

        // Count employees per branch
        $totalUsers = 0;
        $branchStats = [];
        foreach ($branches as $branch) {
            $userCount = User::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->count();
            $totalUsers += $userCount;
            $branchStats[] = [
                'name'  => $branch->name_ar,
                'code'  => $branch->code,
                'users' => $userCount,
            ];
        }

        // Estimate record types based on compliance gauge
        $totalRecords = $workingDays * $totalUsers;
        $absentRate   = max(0, (10 - $gauge) * 2.5); // gauge 10 = 0%, gauge 1 = 22.5%
        $lateRate     = max(0, (10 - $gauge) * 5);    // gauge 10 = 0%, gauge 1 = 45%
        $earlyLeave   = max(0, (10 - $gauge) * 3);    // gauge 10 = 0%, gauge 1 = 27%

        $existingRecords = AttendanceLog::whereIn('branch_id', $branches->pluck('id'))
            ->whereBetween('attendance_date', [$dateFrom->format('Y-m-d'), $dateTo->format('Y-m-d')])
            ->count();

        return [
            'date_from'        => $dateFrom->format('Y-m-d'),
            'date_to'          => $dateTo->format('Y-m-d'),
            'working_days'     => $workingDays,
            'total_users'      => $totalUsers,
            'total_records'    => $totalRecords,
            'branches'         => $branchStats,
            'gauge'            => $gauge,
            'estimated_absent' => round($absentRate, 1),
            'estimated_late'   => round($lateRate, 1),
            'estimated_early'  => round($earlyLeave, 1),
            'existing_records' => $existingRecords,
            'net_new_records'  => max(0, $totalRecords - $existingRecords),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE: Resolve Branches
    // ═══════════════════════════════════════════════════════════
    protected function resolveBranches(array $data): Collection
    {
        $branchIds = $data['branch_ids'] ?? [];

        if (empty($branchIds)) {
            return Branch::where('is_active', true)->get();
        }

        return Branch::whereIn('id', $branchIds)->get();
    }

    // ═══════════════════════════════════════════════════════════
    //  CORE ALGORITHM: Generate Single Attendance Record
    //  Uses Haversine formula (via Branch::distanceTo) for GPS validation.
    // ═══════════════════════════════════════════════════════════
    protected function generateAttendanceRecord(
        User $user,
        Branch $branch,
        Carbon $day,
        string $shiftStart,
        string $shiftEnd,
        int $graceMinutes,
        int $gauge
    ): array {
        $now = now();
        $shiftStartTime = Carbon::parse($day->format('Y-m-d') . ' ' . $shiftStart);
        $shiftEndTime   = Carbon::parse($day->format('Y-m-d') . ' ' . $shiftEnd);

        // ── Determine Scenario Based on Compliance Gauge ──
        $scenario = $this->determineScenario($gauge);

        // ── Cost per minute calculation ──
        $salary = $user->basic_salary ?? 5000;
        $workingDays = $user->working_days_per_month ?? 22;
        $hoursPerDay = $user->working_hours_per_day ?? 8;
        $costPerMinute = round($salary / ($workingDays * $hoursPerDay * 60), 4);

        // ── Haversine-Based GPS Generation ──
        // Generate realistic coordinates near branch using variance from gauge.
        // Higher gauge = tighter cluster around branch center.
        $gpsData = $this->generateHaversineCoordinates($branch, $gauge);

        switch ($scenario) {
            case 'absent':
                return [
                    'user_id'               => $user->id,
                    'branch_id'             => $branch->id,
                    'attendance_date'       => $day->format('Y-m-d'),
                    'check_in_at'           => null,
                    'check_out_at'          => null,
                    'status'                => 'absent',
                    'delay_minutes'         => 0,
                    'early_leave_minutes'   => 0,
                    'overtime_minutes'      => 0,
                    'worked_minutes'        => 0,
                    'cost_per_minute'       => $costPerMinute,
                    'delay_cost'            => round($costPerMinute * $hoursPerDay * 60, 2),
                    'early_leave_cost'      => 0,
                    'overtime_value'        => 0,
                    'check_in_latitude'     => $gpsData['latitude'],
                    'check_in_longitude'    => $gpsData['longitude'],
                    'check_in_within_geofence' => $gpsData['within_geofence'],
                    'check_in_distance_meters' => $gpsData['distance_meters'],
                    'check_in_ip'           => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'check_in_device'       => 'SARH Demo Generator',
                    'notes'                 => 'سجل تجريبي — غائب',
                    'is_manual_entry'       => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

            case 'late':
                $lateMinutes = $this->generateLateMinutes($gauge);
                $checkIn     = $shiftStartTime->copy()->addMinutes($graceMinutes + $lateMinutes);

                // Some may leave earlier if late
                $earlyLeaveChance = max(0, (10 - $gauge) * 5);
                $earlyLeaveMin    = rand(0, 100) < $earlyLeaveChance ? rand(5, 30) : 0;
                $checkOut         = $shiftEndTime->copy()->subMinutes($earlyLeaveMin);
                $workedMinutes    = max(0, (int) $checkIn->diffInMinutes($checkOut));
                $delayCost        = round($lateMinutes * $costPerMinute, 2);
                $earlyLeaveCost   = round($earlyLeaveMin * $costPerMinute, 2);

                return [
                    'user_id'               => $user->id,
                    'branch_id'             => $branch->id,
                    'attendance_date'       => $day->format('Y-m-d'),
                    'check_in_at'           => $checkIn->format('Y-m-d H:i:s'),
                    'check_out_at'          => $checkOut->format('Y-m-d H:i:s'),
                    'status'                => 'late',
                    'delay_minutes'         => $lateMinutes,
                    'early_leave_minutes'   => $earlyLeaveMin,
                    'overtime_minutes'      => 0,
                    'worked_minutes'        => $workedMinutes,
                    'cost_per_minute'       => $costPerMinute,
                    'delay_cost'            => $delayCost,
                    'early_leave_cost'      => $earlyLeaveCost,
                    'overtime_value'        => 0,
                    'check_in_latitude'     => $gpsData['latitude'],
                    'check_in_longitude'    => $gpsData['longitude'],
                    'check_in_within_geofence' => $gpsData['within_geofence'],
                    'check_in_distance_meters' => $gpsData['distance_meters'],
                    'check_in_ip'           => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'check_in_device'       => 'SARH Demo Generator',
                    'notes'                 => "سجل تجريبي — تأخير {$lateMinutes} دقيقة",
                    'is_manual_entry'       => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

            case 'overtime':
                // Check in on time, leave late
                $earlyArrival    = rand(0, 5);
                $checkIn         = $shiftStartTime->copy()->subMinutes($earlyArrival);
                $overtimeMinutes = rand(15, 90);
                $checkOut        = $shiftEndTime->copy()->addMinutes($overtimeMinutes);
                $workedMinutes   = max(0, (int) $checkIn->diffInMinutes($checkOut));
                $overtimeValue   = round($overtimeMinutes * $costPerMinute * 1.5, 2);

                return [
                    'user_id'               => $user->id,
                    'branch_id'             => $branch->id,
                    'attendance_date'       => $day->format('Y-m-d'),
                    'check_in_at'           => $checkIn->format('Y-m-d H:i:s'),
                    'check_out_at'          => $checkOut->format('Y-m-d H:i:s'),
                    'status'                => 'present',
                    'delay_minutes'         => 0,
                    'early_leave_minutes'   => 0,
                    'overtime_minutes'      => $overtimeMinutes,
                    'worked_minutes'        => $workedMinutes,
                    'cost_per_minute'       => $costPerMinute,
                    'delay_cost'            => 0,
                    'early_leave_cost'      => 0,
                    'overtime_value'        => $overtimeValue,
                    'check_in_latitude'     => $gpsData['latitude'],
                    'check_in_longitude'    => $gpsData['longitude'],
                    'check_in_within_geofence' => $gpsData['within_geofence'],
                    'check_in_distance_meters' => $gpsData['distance_meters'],
                    'check_in_ip'           => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'check_in_device'       => 'SARH Demo Generator',
                    'notes'                 => "سجل تجريبي — عمل إضافي {$overtimeMinutes} دقيقة",
                    'is_manual_entry'       => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];

            case 'present':
            default:
                // Normal on-time attendance with slight variance
                $variance   = rand(-3, max(1, (int) ($graceMinutes * 0.5)));
                $checkIn    = $shiftStartTime->copy()->addMinutes($variance);
                $endVariance = rand(-5, 5);
                $checkOut   = $shiftEndTime->copy()->addMinutes($endVariance);
                $workedMinutes = max(0, (int) $checkIn->diffInMinutes($checkOut));

                return [
                    'user_id'               => $user->id,
                    'branch_id'             => $branch->id,
                    'attendance_date'       => $day->format('Y-m-d'),
                    'check_in_at'           => $checkIn->format('Y-m-d H:i:s'),
                    'check_out_at'          => $checkOut->format('Y-m-d H:i:s'),
                    'status'                => 'present',
                    'delay_minutes'         => 0,
                    'early_leave_minutes'   => max(0, -$endVariance),
                    'overtime_minutes'      => 0,
                    'worked_minutes'        => $workedMinutes,
                    'cost_per_minute'       => $costPerMinute,
                    'delay_cost'            => 0,
                    'early_leave_cost'      => round(max(0, -$endVariance) * $costPerMinute, 2),
                    'overtime_value'        => 0,
                    'check_in_latitude'     => $gpsData['latitude'],
                    'check_in_longitude'    => $gpsData['longitude'],
                    'check_in_within_geofence' => $gpsData['within_geofence'],
                    'check_in_distance_meters' => $gpsData['distance_meters'],
                    'check_in_ip'           => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'check_in_device'       => 'SARH Demo Generator',
                    'notes'                 => null,
                    'is_manual_entry'       => false,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Haversine-Based GPS Coordinate Generator
    //
    //  Generates realistic coordinates near branch center.
    //  Variance controlled by compliance gauge:
    //    gauge 10 → within 30% of geofence radius
    //    gauge 1  → up to 200% of geofence radius (outside fence)
    //  Uses Branch::distanceTo() (Haversine) for distance validation.
    // ═══════════════════════════════════════════════════════════
    protected function generateHaversineCoordinates(Branch $branch, int $gauge): array
    {
        $radius = (int) $branch->geofence_radius;

        // Variance factor: higher gauge = tighter clustering
        $maxDistance = match (true) {
            $gauge >= 9 => $radius * 0.3,
            $gauge >= 7 => $radius * 0.6,
            $gauge >= 5 => $radius * 0.9,
            $gauge >= 3 => $radius * 1.3,
            default     => $radius * 2.0,
        };

        // Generate random bearing (0-360 degrees) and distance
        $bearing = deg2rad(rand(0, 360));
        $distance = rand(0, (int) $maxDistance); // meters

        // Convert distance to lat/lng offset using spherical approximation
        $earthRadius = 6371000; // meters
        $branchLat = deg2rad((float) $branch->latitude);
        $branchLng = deg2rad((float) $branch->longitude);

        $newLat = asin(
            sin($branchLat) * cos($distance / $earthRadius)
            + cos($branchLat) * sin($distance / $earthRadius) * cos($bearing)
        );

        $newLng = $branchLng + atan2(
            sin($bearing) * sin($distance / $earthRadius) * cos($branchLat),
            cos($distance / $earthRadius) - sin($branchLat) * sin($newLat)
        );

        $lat = round(rad2deg($newLat), 7);
        $lng = round(rad2deg($newLng), 7);

        // Validate using Haversine via Branch model
        $actualDistance = $branch->distanceTo($lat, $lng);
        $withinGeofence = $actualDistance <= $radius;

        return [
            'latitude'        => $lat,
            'longitude'       => $lng,
            'distance_meters' => (int) $actualDistance,
            'within_geofence' => $withinGeofence,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  Scenario Determination Algorithm
    // ═══════════════════════════════════════════════════════════
    protected function determineScenario(int $gauge): string
    {
        $roll = rand(1, 100);

        // Probabilities based on compliance gauge
        // Higher gauge = more present, less absent/late
        $absentChance  = max(0, (10 - $gauge) * 2.5);     // 10→0%, 5→12.5%, 1→22.5%
        $lateChance    = max(0, (10 - $gauge) * 5);        // 10→0%, 5→25%, 1→45%
        $overtimeChance = min(15, $gauge * 1.5);            // 10→15%, 5→7.5%, 1→1.5%
        // Rest = present

        if ($roll <= $absentChance) {
            return 'absent';
        } elseif ($roll <= $absentChance + $lateChance) {
            return 'late';
        } elseif ($roll <= $absentChance + $lateChance + $overtimeChance) {
            return 'overtime';
        }

        return 'present';
    }

    // ═══════════════════════════════════════════════════════════
    //  Late Minutes Algorithm (based on gauge)
    // ═══════════════════════════════════════════════════════════
    protected function generateLateMinutes(int $gauge): int
    {
        return match (true) {
            $gauge >= 9 => rand(1, 5),
            $gauge >= 7 => rand(3, 20),
            $gauge >= 5 => rand(5, 45),
            $gauge >= 3 => rand(15, 90),
            default     => rand(30, 180),
        };
    }
}
