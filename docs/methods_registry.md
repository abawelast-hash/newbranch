# صرح — سجل الدوال والخدمات (المرجع التقني)
> **الإصدار:** 3.4.1 | **آخر تحديث:** 2026-02-13
> **النطاق:** توثيق كل دالة، ومُحصِّل محسوب، ونطاق استعلام، وثابت، ومعادلة رياضية

---

## فهرس المحتويات

1. [نموذج المستخدم](#1-نموذج-المستخدم-appmodelsuser)
2. [نموذج الفرع](#2-نموذج-الفرع-appmodelsbranch)
3. [نموذج سجل الحضور](#3-نموذج-سجل-الحضور-appmodelsattendancelog)
4. [نموذج التقرير المالي](#4-نموذج-التقرير-المالي-appmodelsfinancialreport)
5. [نموذج بلاغ المبلغين](#5-نموذج-بلاغ-المبلغين-appmodelswhistleblowerreport)
6. [نموذج الدور](#6-نموذج-الدور-appmodelsrole)
7. [نموذج الصلاحية](#7-نموذج-الصلاحية-appmodelspermission)
8. [نموذج القسم](#8-نموذج-القسم-appmodelsdepartment)
9. [نموذج المحادثة](#9-نموذج-المحادثة-appmodelsconversation)
10. [نموذج الرسالة](#10-نموذج-الرسالة-appmodelsmessage)
11. [نموذج التعميم](#11-نموذج-التعميم-appmodelscircular)
12. [نموذج إقرار التعميم](#12-نموذج-إقرار-التعميم)
13. [نموذج تنبيه الأداء](#13-نموذج-تنبيه-الأداء)
14. [نموذج الشارة](#14-نموذج-الشارة-appmodelsbadge)
15. [نموذج حركة النقاط](#15-نموذج-حركة-النقاط)
16. [نموذج تفاعل المصيدة](#16-نموذج-تفاعل-المصيدة)
17. [نموذج طلب الإجازة](#17-نموذج-طلب-الإجازة)
18. [نموذج المناوبة](#18-نموذج-المناوبة-appmodelsshift)
19. [نموذج سجل التدقيق](#19-نموذج-سجل-التدقيق-appmodelsauditlog)
20. [نموذج العطلة](#20-نموذج-العطلة-appmodelsholiday)
21. [نموذج تعيين المناوبة](#21-نموذج-تعيين-المناوبة-appmodelsusershift) **(v3.4)**
22. [نموذج منح الشارة](#22-نموذج-منح-الشارة-appmodelsuserbadge) **(v3.4)**

---

## 1. نموذج المستخدم (`App\Models\User`)

**الملف:** `app/Models/User.php`
**الجدول:** `users`
**السمات:** `HasFactory`, `Notifiable`, `SoftDeletes`

### 1.1 المُحصِّلات المحسوبة (المحرك المالي)

#### `getTotalSalaryAttribute(): float`
- **الغرض:** حساب إجمالي التعويض الشهري
- **المعادلة:** `basic_salary + housing_allowance + transport_allowance + other_allowances`
- **يُرجع:** `float` — إجمالي التعويض الشهري بالريال السعودي
- **الاستخدام:** `$user->total_salary`

#### `getMonthlyWorkingMinutesAttribute(): int`
- **الغرض:** حساب إجمالي دقائق العمل الشهرية
- **المعادلة:** `working_days_per_month × working_hours_per_day × 60`
- **يُرجع:** `int` — إجمالي دقائق العمل المتاحة في الشهر
- **الاستخدام:** `$user->monthly_working_minutes`
- **مثال:** `22 × 8 × 60 = 10,560 دقيقة`

#### `getCostPerMinuteAttribute(): float`
- **الغرض:** **المقياس المالي الأساسي** — تكلفة كل دقيقة تأخير للموظف
- **المعادلة:** `basic_salary ÷ monthly_working_minutes`
- **البرهان الرياضي:**
  ```
  تكلفة_الدقيقة = الراتب_الأساسي / (أيام_العمل_الشهرية × ساعات_العمل_اليومية × 60)

  للراتب = 8000 ريال، 22 يوم، 8 ساعات:
  دقائق_العمل_الشهرية = 22 × 8 × 60 = 10,560
  تكلفة_الدقيقة = 8000 / 10,560 = 0.7576 ريال/دقيقة (مُقرّبة لـ 4 منازل عشرية)
  ```
- **الحماية:** تُرجع `0.0` إذا كانت `monthly_working_minutes <= 0` (منع القسمة على صفر)
- **يُرجع:** `float` — مُقرّبة لـ 4 منازل عشرية
- **الاستخدام:** `$user->cost_per_minute`

#### `getTotalCostPerMinuteAttribute(): float`
- **الغرض:** نفس ما سبق لكن يستخدم **إجمالي التعويضات** وليس الراتب الأساسي فقط
- **المعادلة:** `total_salary ÷ monthly_working_minutes`
- **يُرجع:** `float` — مُقرّبة لـ 4 منازل عشرية
- **الاستخدام:** `$user->total_cost_per_minute`
- **ملاحظة:** يُستخدم في التقارير الشاملة؛ `cost_per_minute` (الأساسي فقط) هو المعيار لحساب تكلفة التأخير

#### `getDailyRateAttribute(): float`
- **الغرض:** حساب معدل الراتب اليومي (يُستخدم لتقدير تكلفة الإجازات)
- **المعادلة:** `basic_salary ÷ working_days_per_month`
- **الحماية:** تُرجع `0.0` إذا كانت `working_days_per_month <= 0`
- **يُرجع:** `float` — مُقرّبة لمنزلتين عشريتين
- **الاستخدام:** `$user->daily_rate`

### 1.2 المُحصِّلات المحسوبة (الترجمة)

#### `getNameAttribute(): string`
- **الغرض:** إرجاع الاسم المحلي حسب لغة التطبيق
- **المنطق:** `app()->getLocale() === 'ar' ? name_ar : name_en`
- **الاستخدام:** `$user->name`

#### `getJobTitleAttribute(): ?string`
- **الغرض:** إرجاع المسمى الوظيفي المحلي
- **المنطق:** نفس نمط `getNameAttribute`
- **الاستخدام:** `$user->job_title`

### 1.3 الدوال المالية

#### `calculateDelayCost(int $minutes): float`
- **الغرض:** حساب التكلفة المالية لعدد معين من دقائق التأخير
- **المعادلة:** `$minutes × cost_per_minute`
- **المعاملات:** `$minutes` — عدد دقائق التأخير
- **يُرجع:** `float` — مُقرّبة لمنزلتين عشريتين
- **مثال:** `$user->calculateDelayCost(15)` → `15 × 0.7576 = 11.36 ريال`

### 1.4 دوال التحكم في الوصول (RBAC)

#### `hasPermission(string $slug): bool`
- **الغرض:** التحقق مما إذا كان المستخدم يملك صلاحية محددة عبر دوره
- **المنطق:** `is_super_admin → true` (تجاوز)، وإلا يتحقق من `role.permissions` للمطابقة
- **المعاملات:** `$slug` — معرّف الصلاحية (مثال: `'finance.view_all'`)
- **يُرجع:** `bool`

#### `hasSecurityLevel(int $minimumLevel): bool`
- **الغرض:** التحقق مما إذا كان المستوى الأمني للمستخدم يستوفي الحد الأدنى
- **المنطق:** `security_level >= $minimumLevel`
- **المعاملات:** `$minimumLevel` — عدد صحيح من 1 إلى 10

#### `canManage(User $target): bool`
- **الغرض:** تحديد ما إذا كان المستخدم الحالي يمكنه إدارة المستخدم المستهدف
- **المنطق:** `is_super_admin → true`، وإلا `security_level > target.security_level`
- **التصميم:** يستخدم `>` صارمة (وليس `>=`) — الأقران لا يمكنهم إدارة بعضهم

### 1.5 الدوال الأمنية (تجاوز $fillable)

#### `setSecurityLevel(int $level): self`
- **الغرض:** تعيين المستوى الأمني للمستخدم (غير قابل للتعيين الجماعي)
- **المنطق:** يستخدم `forceFill()` لتجاوز `$fillable`. يُقيّد القيمة بين 1-10 عبر `max(1, min($level, 10))`
- **يُرجع:** `self` (قابل للتسلسل)

#### `promoteToSuperAdmin(): self`
- **الغرض:** منح صلاحيات المدير الأعلى
- **المنطق:** يضبط `is_super_admin = true` و `security_level = 10` عبر `forceFill()`
- **يُرجع:** `self` (قابل للتسلسل)

#### `enableTrapMonitoring(): self`
- **الغرض:** تحديد المستخدم لمراقبة المصائد النفسية
- **المنطق:** يضبط `is_trap_target = true` عبر `forceFill()`
- **يُرجع:** `self` (قابل للتسلسل)

#### `recordLogin(string $ip): void`
- **الغرض:** تسجيل عملية دخول ناجحة (مسار التدقيق الأمني)
- **المنطق:** يُحدّث `last_login_at`، `last_login_ip`، يُصفّر `failed_login_attempts` و `locked_until`

### 1.6 الدوال الثابتة (Static)

#### `generateEmployeeId(): string`
- **الغرض:** توليد رقم بطاقة موظف فريد تلقائياً
- **التنسيق:** `SARH-{YY}-{0001}` (مثال: `SARH-26-0042`)
- **المنطق:** يعد جميع المستخدمين (بما فيهم المحذوفين ناعمياً) + 1، يُكمّل بأصفار لـ 4 أرقام
- **يُستدعى:** تلقائياً في `booted()` عبر حدث `creating`

### 1.7 نطاقات الاستعلام

| النطاق | التوقيع | التأثير على SQL |
|--------|---------|----------------|
| `scopeActive` | `($query)` | `WHERE status = 'active'` |
| `scopeInBranch` | `($query, int $branchId)` | `WHERE branch_id = ?` |
| `scopeInDepartment` | `($query, int $departmentId)` | `WHERE department_id = ?` |
| `scopeWithSecurityLevel` | `($query, int $minLevel)` | `WHERE security_level >= ?` |

### 1.8 العلاقات

| الدالة | النوع | النموذج المرتبط | المفتاح الأجنبي/الجدول الوسيط |
|--------|------|-----------------|-------------------------------|
| `branch()` | `BelongsTo` | `Branch` | `branch_id` |
| `department()` | `BelongsTo` | `Department` | `department_id` |
| `role()` | `BelongsTo` | `Role` | `role_id` |
| `directManager()` | `BelongsTo` | `User` | `direct_manager_id` |
| `subordinates()` | `HasMany` | `User` | `direct_manager_id` |
| `attendanceLogs()` | `HasMany` | `AttendanceLog` | `user_id` |
| `financialReports()` | `HasMany` | `FinancialReport` | `user_id` |
| `leaveRequests()` | `HasMany` | `LeaveRequest` | `user_id` |
| `conversations()` | `BelongsToMany` | `Conversation` | `conversation_participants` |
| `sentMessages()` | `HasMany` | `Message` | `sender_id` |
| `performanceAlerts()` | `HasMany` | `PerformanceAlert` | `user_id` |
| `badges()` | `HasMany` | `UserBadge` | `user_id` |
| `awardedBadges()` | `HasMany` (with badge) | `UserBadge` | eager-loads `badge` |
| `pointsTransactions()` | `HasMany` | `PointsTransaction` | `user_id` |
| `trapInteractions()` | `HasMany` | `TrapInteraction` | `user_id` |
| `shifts()` | `HasMany` | `UserShift` | `user_id` |
| `activeShift()` | دالة | `UserShift` | `active()` + `current()` scope |
| `currentShift()` | دالة | `Shift` | عبر `activeShift()?->shift` (توافق عكسي) |
| `shiftHistory()` | `HasMany` | `UserShift` | مرتب من الأحدث |

### 1.9 الحقول المحمية (ليست ضمن `$fillable`)

| الحقل | سبب الحماية | دالة التعيين |
|-------|-------------|--------------|
| `is_super_admin` | صلاحية النظام النهائية | `promoteToSuperAdmin()` |
| `security_level` | مستوى RBAC — صلاحيات متتالية | `setSecurityLevel(int)` |
| `is_trap_target` | مراقبة النزاهة السرية | `enableTrapMonitoring()` |
| `last_login_at` | يُدار بواسطة النظام | `recordLogin(string)` |
| `last_login_ip` | يُدار بواسطة النظام | `recordLogin(string)` |
| `failed_login_attempts` | يُدار بواسطة النظام | `recordLogin(string)` |
| `locked_until` | يُدار بواسطة النظام | `recordLogin(string)` |

---

## 2. نموذج الفرع (`App\Models\Branch`)

**الملف:** `app/Models/Branch.php`
**الجدول:** `branches`

### 2.1 دوال السياج الجغرافي

#### `distanceTo(float $lat, float $lng): float`
- **الغرض:** حساب المسافة بالأمتار بين مركز الفرع والإحداثيات المُعطاة
- **الخوارزمية:** صيغة هافرساين (Haversine)
- **البرهان الرياضي:**
  ```
  نصف قطر الأرض (R) = 6,371,000 متر

  a = sin²(Δlat/2) + cos(lat₁) × cos(lat₂) × sin²(Δlng/2)
  c = 2 × atan2(√a, √(1-a))
  المسافة = R × c
  ```
- **المعاملات:** `$lat`, `$lng` — إحداثيات GPS للموظف
- **يُرجع:** `float` — المسافة بالأمتار، مُقرّبة لمنزلتين عشريتين

#### `isWithinGeofence(float $lat, float $lng): bool`
- **الغرض:** التحقق مما إذا كانت الإحداثيات داخل السياج الجغرافي للفرع
- **المنطق:** `distanceTo(lat, lng) <= geofence_radius`
- **نصف القطر الافتراضي:** 17 متراً (قابل للتخصيص لكل فرع)

### 2.2 الدوال المالية

#### `recalculateSalaryBudget(): void`
- **الغرض:** تحديث `monthly_salary_budget` المُخزّن مؤقتاً من الموظفين النشطين
- **المنطق:** `SUM(basic_salary) WHERE branch_id = this AND status = 'active'`
- **يُستدعى:** يدوياً أو عبر مهمة مجدولة

### 2.3 العلاقات

| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `users()` | `HasMany` | `User` |
| `departments()` | `HasMany` | `Department` |
| `attendanceLogs()` | `HasMany` | `AttendanceLog` |
| `financialReports()` | `HasMany` | `FinancialReport` |
| `holidays()` | `HasMany` | `Holiday` |

---

## 3. نموذج سجل الحضور (`App\Models\AttendanceLog`)

**الملف:** `app/Models/AttendanceLog.php`
**الجدول:** `attendance_logs`

### 3.1 الدوال المالية

#### `calculateFinancials(): self`
- **الغرض:** أخذ لقطة فورية من المعدل المالي للموظف وحساب جميع التكاليف
- **المنطق:**
  ```php
  cost_per_minute  = user.cost_per_minute  // لقطة من المُحصِّل
  delay_cost       = delay_minutes × cost_per_minute
  early_leave_cost = early_leave_minutes × cost_per_minute
  overtime_value   = overtime_minutes × cost_per_minute × 1.5  // معدل 1.5x
  ```
- **يُرجع:** `self` (قابل للتسلسل — استدعِ `->save()` بعده)
- **حرج:** يجب استدعاؤها عند تسجيل الحضور لأخذ لقطة من معدل الراتب الحالي

#### `evaluateAttendance(string $shiftStart, int $gracePeriod = 5): self`
- **الغرض:** تحديد حالة الحضور ودقائق التأخير
- **المنطق:**
  ```
  إذا لا يوجد check_in_at → الحالة = 'غياب'
  وإلا إذا check_in_at ≤ (بداية_المناوبة + فترة_السماح) → الحالة = 'حاضر'، التأخير = 0
  وإلا → الحالة = 'متأخر'، دقائق_التأخير = الفرق(check_in_at, بداية_المناوبة)
  ```
- **المعاملات:**
  - `$shiftStart` — نص الوقت مثال: `'08:00'`
  - `$gracePeriod` — دقائق السماح (الافتراضي: 5)

### 3.2 نطاقات الاستعلام

| النطاق | التأثير |
|--------|---------|
| `scopeForDate($query, $date)` | `WHERE attendance_date = ?` |
| `scopeLate($query)` | `WHERE status = 'late'` |
| `scopeAbsent($query)` | `WHERE status = 'absent'` |
| `scopeWithDelayCost($query)` | `WHERE delay_cost > 0` |
| `scopeTotalDelayCost($query)` | تُرجع `SUM(delay_cost)` كعدد عشري |

### 3.3 العلاقات

| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `user()` | `BelongsTo` | `User` |
| `branch()` | `BelongsTo` | `Branch` |
| `approvedByUser()` | `BelongsTo` | `User` (عبر `approved_by`) |

---

## 4. نموذج التقرير المالي (`App\Models\FinancialReport`)

**الملف:** `app/Models/FinancialReport.php`
**الجدول:** `financial_reports`

### 4.1 إنشاء التقارير

#### `static generateForEmployee(User $user, string $start, string $end): self`
- **الغرض:** بناء تقرير مالي شامل لموظف واحد خلال نطاق زمني
- **المنطق:**
  1. استعلام جميع `AttendanceLogs` للمستخدم في النطاق الزمني
  2. تجميع: عدد حسب الحالة، مجاميع الدقائق والتكاليف
  3. حساب `صافي_الأثر_المالي = تكلفة_التأخير + تكلفة_الخروج_المبكر - تكلفة_العمل_الإضافي`
  4. حساب `نسبة_الخسارة = (إجمالي_تكلفة_التأخير / ميزانية_الرواتب) × 100`
- **يُرجع:** مثيل `FinancialReport` غير محفوظ (استدعِ `->save()` للحفظ)
- **ملاحظة:** `report_code` يُنشأ تلقائياً كـ `FIN-EMP-{employee_id}-{timestamp}`

#### `static generateReportCode(string $scope): string`
- **الغرض:** توليد معرّف تقرير فريد
- **التنسيق:** `FIN-{PREFIX_النطاق}-{YmdHis}-{3_أرقام_عشوائية}`
- **مثال:** `FIN-BRA-20260207143022-042`

### 4.2 نطاقات الاستعلام

| النطاق | التأثير |
|--------|---------|
| `scopeForPeriod($query, $start, $end)` | `WHERE period_start BETWEEN ? AND ?` |
| `scopeByScope($query, $scope)` | `WHERE scope = ?` |

### 4.3 العلاقات

| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `user()` | `BelongsTo` | `User` |
| `branch()` | `BelongsTo` | `Branch` |
| `department()` | `BelongsTo` | `Department` |
| `generatedByUser()` | `BelongsTo` | `User` (عبر `generated_by`) |

---

## 5. نموذج بلاغ المبلغين (`App\Models\WhistleblowerReport`)

**الملف:** `app/Models/WhistleblowerReport.php`

### 5.1 دوال التشفير

#### `setContent(string $plainText): self`
- **الغرض:** تشفير وتخزين نص البلاغ
- **المنطق:** `encrypted_content = encrypt($plainText)` (تشفير Laravel AES-256-CBC)

#### `getContent(): string`
- **الغرض:** فك تشفير وإرجاع نص البلاغ
- **المنطق:** `decrypt($this->encrypted_content)`

### 5.2 الدوال الثابتة

#### `static generateTicketNumber(): string`
- **التنسيق:** `WB-{8_أحرف_ست_عشرية}-{yymmdd}`
- **مثال:** `WB-A3F1B2C4-260207`

#### `static generateAnonymousToken(): string`
- **الخوارزمية:** `SHA-256(random_bytes(32) + microtime)`
- **الغرض:** رمز مُشفّر للمتابعة المجهولة

---

## 6. نموذج الدور (`App\Models\Role`)

### 6.1 الدوال

| الدالة | التوقيع | الغرض |
|--------|---------|-------|
| `grantPermission` | `(Permission $permission): void` | إضافة صلاحية عبر `syncWithoutDetaching` |
| `revokePermission` | `(Permission $permission): void` | إزالة صلاحية عبر `detach` |
| `hasPermission` | `(string $slug): bool` | التحقق مما إذا كان الدور يملك صلاحية محددة |

---

## 7. نموذج الصلاحية (`App\Models\Permission`)

### 7.1 النطاقات

| النطاق | التأثير |
|--------|---------|
| `scopeInGroup($query, string $group)` | `WHERE group = ?` |

---

## 8. نموذج القسم (`App\Models\Department`)

### 8.1 العلاقات

| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `branch()` | `BelongsTo` | `Branch` |
| `parent()` | `BelongsTo` | `Department` (ذاتي) |
| `children()` | `HasMany` | `Department` (ذاتي) |
| `head()` | `BelongsTo` | `User` (عبر `head_id`) |
| `users()` | `HasMany` | `User` |
| `financialReports()` | `HasMany` | `FinancialReport` |

---

## 9-12. نماذج المراسلات

### المحادثة (Conversation)

| الدالة | النوع | المرتبط |
|--------|------|---------|
| `creator()` | `BelongsTo` | `User` (عبر `created_by`) |
| `participants()` | `BelongsToMany` | `User` (الجدول الوسيط: `conversation_participants`) |
| `messages()` | `HasMany` | `Message` |
| `latestMessage()` | `HasOne` | `Message` (`latestOfMany`) |

### الرسالة (Message)
| الدالة | النوع | المرتبط |
|--------|------|---------|
| `conversation()` | `BelongsTo` | `Conversation` |
| `sender()` | `BelongsTo` | `User` (عبر `sender_id`) |

### التعميم (Circular)
| الدالة | النوع | المرتبط |
|--------|------|---------|
| `creator()` | `BelongsTo` | `User` (عبر `created_by`) |
| `targetBranch()` | `BelongsTo` | `Branch` |
| `targetDepartment()` | `BelongsTo` | `Department` |
| `targetRole()` | `BelongsTo` | `Role` |
| `acknowledgments()` | `HasMany` | `CircularAcknowledgment` |

**النطاقات:** `scopePublished`، `scopeActive` (منشور + غير منتهي الصلاحية)

---

## 13. نموذج تنبيه الأداء (PerformanceAlert)

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeUnread($query)` | `WHERE is_read = false` |
| `scopeCritical($query)` | `WHERE severity = 'critical'` |

---

## 14. نموذج الشارة (`App\Models\Badge`)

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeActive($query)` | `WHERE is_active = true` |
| `scopeByCategory($query, string $category)` | `WHERE category = ?` |
### العلاقات (v3.4 مُحدّث)

| الدالة | النوع | النموذج المرتبط | الوصف |
|--------|------|-----------------|-------|
| `awards()` | `HasMany` | `UserBadge` | جميع منح هذه الشارة |

> **ℹ️ v3.4:** تم استبدال `users(): BelongsToMany` بـ `awards(): HasMany(UserBadge)` — الوصول للموظفين عبر `$badge->awards()->with('user')`
---

## 15. نموذج حركة النقاط (PointsTransaction)

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeEarned($query)` | `WHERE type = 'earned'` |
| `scopeDeducted($query)` | `WHERE type = 'deducted'` |

### العلاقة متعددة الأشكال
- `sourceable()` — `MorphTo` — أي نموذج يمكن أن يكون مصدر النقاط

---

## 16. نموذج تفاعل المصيدة (TrapInteraction)

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeUnreviewed($query)` | `WHERE is_reviewed = false` |
| `scopeHighRisk($query)` | `WHERE risk_level IN ('high', 'critical')` |

---

## 17. نموذج طلب الإجازة (LeaveRequest)

#### `calculateCost(): self`
- **الغرض:** تقدير تكلفة الإجازة بناءً على المعدل اليومي
- **المعادلة:** `إجمالي_الأيام × user.daily_rate`

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopePending($query)` | `WHERE status = 'pending'` |
| `scopeApproved($query)` | `WHERE status = 'approved'` |

---

## 18. نموذج المناوبة (`App\Models\Shift`)

#### `getDurationMinutesAttribute(): int`
- **الغرض:** حساب مدة المناوبة بالدقائق
- **المنطق:** يتعامل مع المناوبات الليلية (النهاية < البداية → إضافة 24 ساعة)
- **الاستخدام:** `$shift->duration_minutes`

### العلاقات (v3.4 مُحدّث)

| الدالة | النوع | النموذج المرتبط | الوصف |
|--------|------|-----------------|-------|
| `assignments()` | `HasMany` | `UserShift` | جميع تعيينات هذا الشفت |
| `currentlyAssignedUsers()` | `HasMany` (scoped) | `UserShift` | الموظفون المعيّنون حالياً (`active()` + `current()`) |

> **ℹ️ v3.4:** تم استبدال `users(): BelongsToMany` بـ `assignments(): HasMany(UserShift)` — الوصول للموظفين عبر `$shift->assignments()->with('user')`

---

## 19. نموذج سجل التدقيق (`App\Models\AuditLog`)

#### `static record(string $action, ?Model $model, ?array $old, ?array $new, ?string $description): self`
- **الغرض:** مساعد تسجيل سريع — يلتقط المستخدم وعنوان IP ومعرّف المتصفح تلقائياً
- **الاستخدام:** `AuditLog::record('update', $user, $oldData, $newData, 'تم تغيير الراتب')`

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeForModel($query, string $type, int $id)` | التصفية حسب الهدف متعدد الأشكال |
| `scopeByAction($query, string $action)` | `WHERE action = ?` |

---

## 20. نموذج العطلة (`App\Models\Holiday`)

#### `static isHoliday(Carbon $date, ?int $branchId = null): bool`
- **الغرض:** التحقق مما إذا كان تاريخ معين عطلة (عامة أو لفرع محدد)
- **المنطق:** يتحقق من `WHERE date = ? AND (branch_id IS NULL OR branch_id = ?)`

---

## 21. خدمة السياج الجغرافي (`App\Services\GeofencingService`)

**الملف:** `app/Services/GeofencingService.php`

#### `validatePosition(Branch $branch, float $lat, float $lng): array`
- **الغرض:** التحقق من إحداثيات GPS مقابل السياج الجغرافي للفرع
- **يُرجع:** `['distance_meters' => float, 'within_geofence' => bool]`
- **المنطق:** يُفوّض إلى `Branch::distanceTo()` و `Branch::isWithinGeofence()`
- **مثال:** `$result = $service->validatePosition($branch, 24.7136, 46.6753)`

#### `static haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float`
- **الغرض:** أداة ثابتة لحساب مسافة هافرساين (بالأمتار)
- **الخوارزمية:** نفس `Branch::distanceTo()` لكن بدون حالة
- **يُرجع:** `float` — أمتار، مُقرّبة لمنزلتين عشريتين

---

## 22. خدمة الحضور (`App\Services\AttendanceService`)

**الملف:** `app/Services/AttendanceService.php`

#### `checkIn(User $user, float $lat, float $lng, ?string $ip, ?string $device): AttendanceLog`
- **الغرض:** سير عملية تسجيل الحضور الكامل مع السياج الجغرافي + اللقطة المالية
- **الخطوات:**
  1. تحميل فرع المستخدم (`$user->branch`)
  2. التحقق من السياج عبر `GeofencingService::validatePosition()`
  3. الرفض إذا خارج السياج (يرمي `OutOfGeofenceException`)
  4. تحديد المناوبة عبر `$user->currentShift()` أو الإعدادات الافتراضية للفرع
  5. إنشاء `AttendanceLog` ببيانات GPS
  6. استدعاء `evaluateAttendance(shift_start, grace_period)` → تحديد الحالة + التأخير
  7. استدعاء `calculateFinancials()` → أخذ لقطة تكلفة_الدقيقة + حساب التكاليف
  8. الحفظ والإرجاع
- **يرمي:** `OutOfGeofenceException` إذا كان `within_geofence === false`
- **يُرجع:** مثيل `AttendanceLog` محفوظ

#### `checkOut(User $user, float $lat, float $lng): AttendanceLog`
- **الغرض:** سير عملية تسجيل الانصراف الكامل مع حساب العمل الإضافي/الخروج المبكر
- **الخطوات:**
  1. البحث عن `AttendanceLog` اليوم للمستخدم
  2. التحقق من السياج لإحداثيات الانصراف
  3. حساب `worked_minutes` من فرق الحضور/الانصراف
  4. المقارنة مع مدة المناوبة → عمل إضافي أو خروج مبكر
  5. إعادة حساب البيانات المالية
  6. الحفظ والإرجاع
- **يرمي:** `ModelNotFoundException` إذا لم يكن هناك تسجيل حضور لليوم
- **يُرجع:** مثيل `AttendanceLog` مُحدّث

#### `calculateDelayCost(User $user, int $minutesDelayed): float`
- **الغرض:** غلاف لـ `User::calculateDelayCost()` — متاح كدالة خدمية
- **المعادلة:** `(الراتب_الأساسي / أيام_العمل / ساعات_العمل / 60) × دقائق_التأخير`
- **يُرجع:** `float` — مُقرّبة لمنزلتين عشريتين

---

## 23. متحكم الحضور (`App\Http\Controllers\AttendanceController`)

**الملف:** `app/Http/Controllers/AttendanceController.php`

#### `checkIn(Request $request): JsonResponse`
- **المسار:** `POST /attendance/check-in`
- **التحقق:** `latitude` (مطلوب، رقمي، -90..90)، `longitude` (مطلوب، رقمي، -180..180)
- **المصادقة:** مستخدم مُصادق عليه (وسيط `auth`)
- **يُرجع:** `201` مع JSON من AttendanceLog أو `422` إذا خارج السياج

#### `checkOut(Request $request): JsonResponse`
- **المسار:** `POST /attendance/check-out`
- **التحقق:** نفس حقول GPS
- **يُرجع:** `200` مع JSON من AttendanceLog المُحدّث

#### `todayStatus(Request $request): JsonResponse`
- **المسار:** `GET /attendance/today`
- **يُرجع:** سجل حضور اليوم أو `null` إذا لم يُسجّل الحضور

---

## 24. نموذج المصيدة (`App\Models\Trap`)

**الملف:** `app/Models/Trap.php`
**الجدول:** `traps`

#### `getNameAttribute(): string`
- **الغرض:** إرجاع اسم المصيدة المحلي
- **المنطق:** `app()->getLocale() === 'ar' ? name_ar : name_en`

#### `deriveRiskLevel(): string`
- **الغرض:** تحويل `risk_weight` (1-10) إلى مستوى خطورة مقروء
- **المنطق:**
  ```
  1-3  → 'منخفض' (low)
  4-6  → 'متوسط' (medium)
  7-8  → 'مرتفع' (high)
  9-10 → 'حرج' (critical)
  ```
- **يُرجع:** `string` — إحدى القيم: `low`، `medium`، `high`، `critical`

### النطاقات
| النطاق | التأثير |
|--------|---------|
| `scopeActive($query)` | `WHERE is_active = true` |
| `scopeByCode($query, string $code)` | `WHERE trap_code = ?` |

### العلاقات
| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `interactions()` | `HasMany` | `TrapInteraction` |

---

## 25. نموذج المستخدم — إضافات تقييم مخاطر المصائد

### دوال تسجيل المخاطر

#### `incrementRiskScore(): int`
- **الغرض:** حساب وحفظ درجة المخاطر اللوغاريتمية بعد تفاعل مع مصيدة
- **المعادلة:** `risk_score = 10 × (2^n − 1)` حيث `n` = إجمالي عدد تفاعلات المصائد
- **البرهان الرياضي:**
  ```
  n=1: 10 × (2¹ − 1) =   10
  n=2: 10 × (2² − 1) =   30
  n=3: 10 × (2³ − 1) =   70
  n=4: 10 × (2⁴ − 1) =  150
  n=5: 10 × (2⁵ − 1) =  310
  ```
- **الحفظ:** يستخدم `forceFill()` — `risk_score` ليس ضمن `$fillable`
- **يُرجع:** `int` — درجة المخاطر الجديدة

#### `getRiskLevelAttribute(): string`
- **الغرض:** تصنيف مخاطر مقروء بناءً على الدرجة التراكمية
- **المنطق:**
  ```
  الدرجة < 30   → 'منخفض' (low)
  الدرجة < 100  → 'متوسط' (medium)
  الدرجة < 300  → 'مرتفع' (high)
  الدرجة >= 300 → 'حرج' (critical)
  ```

---

## 26. خدمة استجابة المصائد (`App\Services\TrapResponseService`)

**الملف:** `app/Services/TrapResponseService.php`

#### `triggerTrap(User $user, string $trapCode, Request $request): array`
- **الغرض:** سير عملية تفعيل المصيدة الكامل: تسجيل التفاعل → زيادة المخاطر → إنشاء استجابة وهمية
- **الخطوات:**
  1. تحديد `Trap` بواسطة `trap_code`
  2. إنشاء سجل `TrapInteraction` مع IP ومعرّف المتصفح والبيانات الوصفية
  3. استدعاء `User::incrementRiskScore()`
  4. استدعاء `generateFakeResponse()` لنوع المصيدة
- **يُرجع:** `array` — حمولة الاستجابة الوهمية لعرضها في تطبيق PWA
- **يرمي:** `ModelNotFoundException` إذا كان trap_code غير صالح

#### `generateFakeResponse(Trap $trap): array`
- **الغرض:** إنتاج تعليقات واجهة مُقنعة وهمية لكل نوع مصيدة
- **يُرجع:** مصفوفة مفتاحية مع استجابة خاصة بنوع المصيدة:
  ```php
  // استراق النظر على الرواتب (SALARY_PEEK)
  ['type' => 'table', 'data' => [...صفوف رواتب وهمية...]]

  // تصعيد الصلاحيات (PRIVILEGE_ESCALATION)
  ['type' => 'success', 'message' => 'تم منح وصول مؤقت كمدير']

  // تجاوز النظام (SYSTEM_BYPASS)
  ['type' => 'warning', 'message' => 'تم إيقاف نظام الحضور لمدة 24 ساعة']

  // تصدير البيانات (DATA_EXPORT)
  ['type' => 'download', 'progress' => 100, 'url' => '/exports/fake_...csv']
  ```

---

## 27. متحكم المصائد (`App\Http\Controllers\TrapController`)

**الملف:** `app/Http/Controllers/TrapController.php`

#### `trigger(Request $request): JsonResponse`
- **المسار:** `POST /traps/trigger`
- **التحقق:** `trap_code` (مطلوب، موجود في:traps,trap_code)
- **المصادقة:** مستخدم مُصادق عليه
- **يُرجع:** `200` مع حمولة الاستجابة الوهمية

---

## 28. مكونات Livewire — تطبيق الموظف (PWA)

### لوحة تحكم الموظف (`App\Livewire\EmployeeDashboard`)
- **التحميل:** يُحمّل المستخدم المُصادق عليه مع العلاقات
- **العرض:** `livewire.employee-dashboard` — حاوية لجميع الودجات الأربع

### ودجة الحضور (`App\Livewire\AttendanceWidget`)
- **الغرض:** عرض حالة الحضور اليومي مع تسجيل الحضور/الانصراف عبر GPS
- **الخصائص:** `$status`، `$checkInTime`، `$checkOutTime`
- **الدوال:**
  - `checkIn()` — تستدعي AttendanceService مع الموقع الجغرافي
  - `checkOut()` — تستدعي AttendanceService لتسجيل الانصراف

### ودجة التقييم (`App\Livewire\GamificationWidget`)
- **الغرض:** عرض النقاط والسلسلة الحالية والشارات المكتسبة
- **الخصائص:** `$totalPoints`، `$currentStreak`، `$badges`

### الودجة المالية (`App\Livewire\FinancialWidget`)
- **الغرض:** عرض "درجة الانضباط" — تأثير تكلفة التأخير
- **الخصائص:** `$delayCost`، `$onTimeRate`، `$monthlyLogs`

### ودجة التعاميم (`App\Livewire\CircularsWidget`)
- **الغرض:** عرض قائمة التعاميم النشطة مع حالة الإقرار
- **الدوال:**
  - `acknowledge(int $circularId)` — تُنشئ سجل CircularAcknowledgment

---

## 29. مكونات Livewire — نظام البلاغات السرية

### نموذج البلاغ السري (`App\Livewire\WhistleblowerForm`)
- **الغرض:** تقديم بلاغ مشفّر مجهول
- **الخصائص:** `$category`، `$severity`، `$content`
- **الدوال:**
  - `submit()` — تُشفّر المحتوى، تُنشئ رقم تذكرة + رمز مجهول، تحفظ البلاغ
  - تُرجع ticket_number + anonymous_token (تُعرض مرة واحدة فقط)
- **الأمان:** لا تتطلب مصادقة. لا يُخزّن مفتاح أجنبي للمستخدم.

### تتبع البلاغ (`App\Livewire\WhistleblowerTrack`)
- **الغرض:** تتبع حالة البلاغ بالرمز المجهول
- **الخصائص:** `$token`، `$report`
- **الدوال:**
  - `track()` — يبحث عن البلاغ بواسطة anonymous_token، يعرض الحالة (بدون المحتوى)

---

## 30. مكونات Livewire — المراسلات

### صندوق الوارد (`App\Livewire\MessagingInbox`)
- **الغرض:** عرض قائمة جميع المحادثات مع معاينة آخر رسالة
- **الخصائص:** `$conversations`، `$unreadCount`
- **التحديث الدوري:** تحديث كل 5 ثوانٍ للرسائل الجديدة

### المحادثة (`App\Livewire\MessagingChat`)
- **الغرض:** عرض محادثة واحدة مع فقاعات الرسائل
- **الخصائص:** `$conversation`، `$messages`، `$newMessage`
- **الدوال:**
  - `sendMessage()` — تُنشئ سجل رسالة، تُحدّد رسائل المُرسل كمقروءة
  - `markAsRead()` — تُحدّث حالة القراءة عند التحميل + التحديث
- **التحديث الدوري:** تحديث كل 3 ثوانٍ للرسائل الجديدة

---

## 31. متحكمات ومسارات تطبيق PWA

### متحكم لوحة التحكم (`App\Http\Controllers\DashboardController`)
- **المسار:** `GET /dashboard` → لوحة تحكم الموظف
- **المصادقة:** مطلوبة (وسيط `auth`)

### متحكم البلاغات السرية (`App\Http\Controllers\WhistleblowerController`)
- **المسارات:**
  - `GET /whistleblower` → نموذج البلاغ المجهول (بدون مصادقة)
  - `GET /whistleblower/track` → تتبع البلاغ بالرمز (بدون مصادقة)

### متحكم المراسلات (`App\Http\Controllers\MessagingController`)
- **المسارات:**
  - `GET /messaging` → صندوق الوارد (مصادقة مطلوبة)
  - `GET /messaging/{conversation}` → عرض المحادثة (مصادقة مطلوبة)

---

---

## §32. خدمة التقارير المالية (المرحلة 4)

| الدالة | التوقيع | يُرجع | الوصف |
|--------|---------|-------|-------|
| `getDailyLoss` | `(Carbon $date, ?int $branchId): float` | float | إجمالي تكلفة التأخير عبر الفروع لتاريخ معين |
| `getBranchPerformance` | `(Carbon $month): Collection` | Collection | إحصائيات لكل فرع: إجمالي_الموظفين، معدل_الحضور، التزام_السياج، إجمالي_الخسارة |
| `getDelayImpactAnalysis` | `(string $start, string $end, string $scope, ?int $scopeId): array` | array | تحليل العائد: الخسارة_المحتملة، الخسارة_الفعلية، نسبة_العائد، وفورات_الانضباط |
| `getPredictiveMonthlyLoss` | `(Carbon $month): array` | array | توقعات تنبؤية: متوسط_الخسارة_اليومية، الخسارة_المتراكمة، الأيام_المتبقية، الإجمالي_المتوقع |

---

## §33. ودجات لوحة تحكم Filament (المرحلة 4)

| الودجة | الفئة الأب | الدوال الرئيسية |
|--------|-----------|----------------|
| `RealTimeLossCounter` | StatsOverviewWidget | `getStats()` — خسارة اليوم، عدد المتأخرين، عدد الغائبين، اتجاه الخسارة |
| `BranchPerformanceHeatmap` | TableWidget | `table()` — صفوف الفروع مع معدل_الحضور، الالتزام، أعمدة الخسارة، مُلوّنة |
| `IntegrityAlertHub` | TableWidget | `table()` — آخر تفعيلات المصائد + حالات البلاغات (بوابة المستوى 10) |

---

## §34. صفحات القبو للمستوى 10 (المرحلة 4)

| الصفحة | المسار | الدوال |
|--------|--------|--------|
| `WhistleblowerVaultPage` | `/admin/whistleblower-vault` | `table()` — عرض البلاغات المُفكّك تشفيرها؛ `viewReport()` — فك تشفير مُسجّل في التدقيق |
| `TrapAuditPage` | `/admin/trap-audit` | `table()` — مسار التدقيق الكامل للتفاعلات؛ بيانات مسار المخاطر |

---

## سجل التغييرات

| التاريخ | الإصدار | التغييرات |
|---------|---------|----------|
| 2026-02-07 | 1.0.0 | السجل الأولي — 20 نموذج، 50+ دالة مُوثّقة |
| 2026-02-07 | 1.1.0 | المرحلة 1 — خدمة السياج الجغرافي (دالتان)، خدمة الحضور (3 دوال)، متحكم الحضور (3 نقاط وصول) |
| 2026-02-07 | 1.2.0 | المرحلة 2 — نموذج المصيدة (دالتان، نطاقان)، إضافات مخاطر المستخدم (دالتان)، خدمة استجابة المصائد (دالتان)، متحكم المصائد (نقطة وصول واحدة) |
| 2026-02-07 | 1.3.0 | المرحلة 3 — 8 مكونات Livewire، 3 متحكمات، 6 مسارات، تخطيط PWA، سير تشفير البلاغات، المراسلات مع إيصالات القراءة |
| 2026-02-08 | 1.4.0 | المرحلة 4 — خدمة التقارير المالية (4 دوال)، 3 ودجات لوحة تحكم، صفحتا قبو للمستوى 10، خوارزمية التحليلات التنبؤية |
| 2026-02-08 | 1.5.0 | المرحلة 5 (النهائية) — أمر SarhInstall، سياسة نطاق الفرع، طبقة التخزين المالي المؤقت، فهارس الأداء، تقوية ثنائية اللغة |
| 2026-02-13 | 3.4.0 | **إعادة هيكلة معمارية:** UserShift + UserBadge كنماذج كيانات مستقلة، تحويل BelongsToMany→HasMany في User/Shift/Badge، activeShift()، shiftHistory()، awardedBadges()، assignments()، awards()، 5 مصانع، 20 اختبار، FixUserShiftsDataSeeder |
| 2026-02-13 | 3.4.1 | إضافة نموذجين في فهرس الدوال: نموذج تعيين المناوبة (§21) ونموذج منح الشارة (§22) |

---

## §35. تقوية بيئة الإنتاج (المرحلة 5 — النهائية)

### أمر التثبيت (`App\Console\Commands\SarhInstallCommand`)
- **التوقيع:** `sarh:install`
- **الغرض:** تثبيت بأمر واحد — يبذر RBAC والشارات والمصائد ويُنشئ المدير الأعلى الأولي
- **الخطوات:**
  1. `verifyEnvironment()` — يتحقق من إصدار PHP والإضافات وAPP_KEY واتصال قاعدة البيانات
  2. `runMigrations()` — يُنفّذ `php artisan migrate --force`
  3. `seedCoreData()` — يستدعي RolesAndPermissionsSeeder وBadgesSeeder وTrapsSeeder
  4. `createSuperAdmin()` — يطلب تفاعلياً name_ar وname_en والبريد وكلمة المرور → يُنشئ مستخدم بالمستوى 10
  5. `finalizeInstallation()` — `storage:link`، `config:cache`، `route:cache`

### خدمة التقارير المالية — طبقة التخزين المؤقت
- **مدة التخزين:** 300 ثانية (5 دقائق)
- **يُخزّن مؤقتاً:** `getDailyLoss`، `getBranchPerformance`، `getPredictiveMonthlyLoss`
- **لا يُخزّن مؤقتاً:** `getDelayImpactAnalysis` (عند الطلب، يُفعّل من المستخدم)
- **تنسيق المفتاح:** `sarh.{method}.{date/month}.{branch_id?}`

### سياسة نطاق الفرع الأمنية
- **مُطبّقة في:** `getEloquentQuery()` في AttendanceLogResource
- **المنطق:** غير المدير الأعلى يرى بيانات `branch_id` الخاص به فقط
- **المدير الأعلى:** بدون قيود على النطاق

### فهارس الأداء (الترحيل)
- **الجدول:** `attendance_logs` — 3 فهارس جديدة (delay_cost، user_id+status، attendance_date+delay_cost)
- **الجدول:** `trap_interactions` — 3 فهارس جديدة (trap_id، created_at، user_id+created_at)
- **الجدول:** `audit_logs` — فهرسان جديدان (user_id، action)

### إضافات ثنائية اللغة
- **الملف:** `lang/{ar,en}/install.php` — 15+ مفتاح لمخرجات أمر التثبيت

---

## §36. إعادة هيكلة الواجهة — بنية الموارد (الإصدار 1.6.0)

### مورد المستخدمين (`App\Filament\Resources\UserResource`)

| الدالة | الغرض |
|--------|-------|
| `getEloquentQuery()` | نطاق الفرع — غير المدير الأعلى يرى موظفي فرعه فقط |
| `form()` | مخطط الحقول الأساسية الأربعة: الصورة (مطلوبة)، الاسم بالعربي/الإنجليزي، البريد، كلمة المرور، الراتب الأساسي + قسم تنظيمي قابل للطي |
| `table()` | عمود الصورة، شارة رقم الموظف، الاسم، البريد، الفرع، الدور، الراتب، شارة المستوى الأمني مُلوّنة، أيقونة الحالة |
| القيم الافتراضية المخفية | `working_days_per_month=22`، `working_hours_per_day=8`، `locale=ar`، `timezone=Asia/Riyadh` |

### مورد الفروع (`App\Filament\Resources\BranchResource`)

| الدالة | الغرض |
|--------|-------|
| `form()` | قسم الهوية + منتقي خريطة Leaflet.js (ViewField) + حقول الإحداثيات/نصف القطر + أقسام قابلة للطي للمناوبة/العنوان/المالية |
| `table()` | شارة الكود، الأسماء، المدينة، نصف قطر السياج، أوقات المناوبة، فترة السماح، عدد الموظفين، أيقونة النشاط |
| منتقي الخريطة | `filament.forms.components.map-picker` — Alpine.js + Leaflet.js مع ربط ثنائي الاتجاه للإحداثيات/نصف القطر |

### مكون منتقي الخريطة (`resources/views/filament/forms/components/map-picker.blade.php`)

| الميزة | التنفيذ |
|--------|---------|
| محرك الخرائط | Leaflet.js الإصدار 1.9.4 مع طبقات OpenStreetMap |
| العلامة | قابلة للسحب، تُحدّث الإحداثيات عند السحب |
| معالج النقر | النقر على الخريطة يضع العلامة + يُحدّث الإحداثيات |
| دائرة السياج | برتقالي (#f97316)، شفافية 15%، نصف القطر متزامن مع حقل النموذج |
| المراقبون | `$watch('radius')`، `$watch('lat')`، `$watch('lng')` — مزامنة ثنائية الاتجاه |
| المركز الافتراضي | الرياض: 24.7136, 46.6753 |

### AppServiceProvider — بوابات المستوى 10

| البوابة | الشرط | التأثير |
|---------|-------|---------|
| `Gate::before()` | `security_level === 10 \|\| is_super_admin` | تُرجع `true` لجميع عمليات التحقق من الصلاحيات |
| `access-whistleblower-vault` | `security_level >= 10` | الوصول لصفحة القبو |
| `access-trap-audit` | `security_level >= 10` | الوصول لصفحة تدقيق المصائد |
| `bypass-geofence` | `security_level >= 10 \|\| is_super_admin` | تسجيل حضور من أي موقع |

### ملفات اللغة ثنائية اللغة المُضافة
- **الملف:** `lang/{ar,en}/users.php` — 30+ مفتاح لواجهة إدارة الموظفين
- **الملف:** `lang/{ar,en}/branches.php` — 30+ مفتاح لواجهة إدارة الفروع

---

## §37. محرك المنافسة — لوحة ترتيب الفروع وشريط الأخبار (الإصدار 1.7.0)

### بذّار بيانات المشروع (`Database\Seeders\ProjectDataSeeder`)

| الدالة | الغرض |
|--------|-------|
| `run()` | يبذر 5 فروع (إحداثيات GPS + سياج 17 متر) + 36 مستخدم مع `updateOrCreate` لضمان عدم التكرار |
| توزيع الفروع | FADA-2: 11، FADA-1: 8، SARH-CORNER: 7، SARH-2: 5، SARH-HQ: 4 |
| كلمة المرور الافتراضية | `Goolbx512@@` لجميع المستخدمين المبذورين |
| المدير الأعلى | `abdullah@sarh.app` (emp001) — security_level=10، total_points=500 |

### صفحة لوحة ترتيب الفروع (`App\Filament\Pages\BranchLeaderboardPage`)

| الدالة | الغرض |
|--------|-------|
| `getBranches()` | يُرتّب الفروع حسب **أقل خسارة مالية** من التأخير. يحسب درجة الانضباط لتعيين المستوى: الأساس 100، -2/متأخر، -5/غائب، +10/موظف مثالي، +0.1×النقاط. يُرجع مصفوفة مُرتّبة مع تعيين 6 مستويات |
| مستويات التصنيف | أسطوري (≥150)، ألماسي (≥120)، ذهبي (≥100)، فضي (≥80)، برونزي (≥60)، مبتدئ (<60) |
| الكأس/السلحفاة | الترتيب حسب أقل خسارة مالية؛ شريط الأخبار يعرض لكل فرع 🏆 أول حضور / 🐢 آخر حضور |

### شريط الأخبار اليومي (`App\Filament\Widgets\DailyNewsTicker`)

| الدالة | الغرض |
|--------|-------|
| `getNewsItems()` | يجمع أخبار المنافسة اليومية: أول/آخر حضور لكل فرع، إحصائيات الحضور، أعلى مُسجّل، إجمالي الموظفين |

---

## §38. نموذج تعيين المناوبة (`App\Models\UserShift`) — v3.4

**الملف:** `app/Models/UserShift.php`
**الجدول:** `user_shifts`
**الفلسفة:** كيان مستقل (ليس Pivot) — عقد تعيين مؤقت بصلاحية زمنية وتدقيق إداري

### العلاقات

| الدالة | النوع | النموذج المرتبط | الوصف |
|--------|------|-----------------|-------|
| `user()` | `BelongsTo` | `User` | الموظف المعيّن |
| `shift()` | `BelongsTo` | `Shift` | المناوبة |
| `assignedByUser()` | `BelongsTo` | `User` | من قام بالتعيين |
| `approvedByUser()` | `BelongsTo` | `User` | من وافق |

### النطاقات

| النطاق | التوقيع | التأثير |
|--------|---------|---------|
| `scopeActive` | `($query)` | `WHERE effective_from <= today AND (effective_to IS NULL OR >= today)` |
| `scopeCurrent` | `($query)` | `WHERE is_current = true` |
| `scopeForUserInPeriod` | `($query, userId, startDate, endDate)` | تعيينات موظف في فترة زمنية |

### منطق الأعمال

#### `isValidOn($date): bool`
- **الغرض:** هل هذا التعيين ساري في تاريخ معين
- **المنطق:** `effective_from <= date && (effective_to null || >= date)`

#### `terminate(?string $reason): void`
- **الغرض:** إنهاء التعيين (يضبط effective_to = أمس + is_current = false)
- **يُسجّل السبب:** في حقل `reason`

#### `makeCurrent(): void`
- **الغرض:** تفعيل هذا التعيين كحالي وإلغاء جميع التعيينات الأخرى للموظف
- **المنطق:** يُحدّث جميع سجلات الموظف الأخرى إلى `is_current = false` ثم يُفعّل هذا السجل

---

## §39. نموذج منح الشارة (`App\Models\UserBadge`) — v3.4

**الملف:** `app/Models/UserBadge.php`
**الجدول:** `user_badges`
**الفلسفة:** كيان مستقل (ليس Pivot) — إنجاز موثق بمانح وسبب ونقاط

### العلاقات

| الدالة | النوع | النموذج المرتبط |
|--------|------|-----------------|
| `user()` | `BelongsTo` | `User` |
| `badge()` | `BelongsTo` | `Badge` |
| `awardedByUser()` | `BelongsTo` | `User` (عبر `awarded_by`) |

### النطاقات

| النطاق | التوقيع | التأثير |
|--------|---------|---------|
| `scopeAwardedBetween` | `($query, $start, $end)` | `WHERE awarded_at BETWEEN ? AND ?` |
| `scopeForUser` | `($query, int $userId)` | `WHERE user_id = ?` |

### منطق الأعمال

#### `static award(int $userId, int $badgeId, int $awardedBy, string $reason): self`
- **الغرض:** منح شارة لموظف مع تسجيل النقاط تلقائياً
- **الخطوات:**
  1. إنشاء سجل `UserBadge` بجميع البيانات
  2. إذا كانت `badge.points_reward > 0`: يزيد `user.total_points` وينشئ `PointsTransaction`
- **يُرجع:** مثيل `UserBadge` المُنشأ
| `getTrophyFirstCheckin()` | 🏆 أول حضور لكل فرع اليوم (أبكر `check_in_at` من AttendanceLog لكل فرع) |
| `getTurtleLastCheckin()` | 🐢 آخر حضور لكل فرع اليوم (أحدث `check_in_at` من AttendanceLog لكل فرع) |

### إجراء النقاط في مورد المستخدمين

| الدالة | الغرض |
|--------|-------|
| إجراء `adjust_points` | إجراء جدول Filament — المستوى 10 يُدخل النقاط + السبب → زيادة `total_points` + سجل في نموذج `PointsTransaction` + إشعار نخب |
| البوابة | `adjust-points` — يتطلب security_level ≥ 10 أو is_super_admin |

### AppServiceProvider — بوابات المنافسة (الإصدار 1.7.0)

| البوابة | الشرط | التأثير |
|---------|-------|---------|
| `manage-competition` | `security_level >= 10 \|\| is_super_admin` | إدارة صفحة المنافسة |
| `adjust-points` | `security_level >= 10 \|\| is_super_admin` | تعديل النقاط يدوياً |

### ملفات اللغة ثنائية اللغة المُضافة (الإصدار 1.7.0)
- **الملف:** `lang/{ar,en}/competition.php` — 30+ مفتاح لواجهة المنافسة (الترتيب، المستويات، الشريط، التسجيل)
- **الملف:** `lang/{ar,en}/users.php` — 7 مفاتيح جديدة لإدارة النقاط

---

## §23. سياسة المستخدم (`App\Policies\UserPolicy`) — v4.0

**الملف:** `app/Policies/UserPolicy.php`
**الغرض:** حماية بيانات الرواتب من الوصول غير المصرح
**التسجيل:** `Gate::policy(User::class, UserPolicy::class)` في `AppServiceProvider`

#### `viewSalary(User $user, User $target): bool`
- **الغرض:** هل يستطيع المستخدم مشاهدة راتب الموظف المستهدف؟
- **المنطق:**
  1. المستوى 10 أو super_admin → `true`
  2. المدير المباشر (`target.direct_manager_id === user.id`) → `true`
  3. المستوى 7+ مع نفس الفرع → `true`
  4. المستوى 6+ مع نفس القسم → `true`
  5. غير ذلك → `false`

#### `updateSalary(User $user, User $target): bool`
- **الغرض:** هل يستطيع تعديل الراتب؟
- **المنطق:** المستوى 10/super_admin أو المستوى 7+ في نفس الفرع

#### `delete(User $user, User $target): bool`
- **الغرض:** هل يستطيع حذف الموظف؟
- **المنطق:** المستوى 10 أو super_admin **فقط**

---

## §24. سياسة سجل الحضور (`App\Policies\AttendanceLogPolicy`) — v4.0

**الملف:** `app/Policies/AttendanceLogPolicy.php`
**الغرض:** تصفية سجلات الحضور حسب الفرع
**التسجيل:** `Gate::policy(AttendanceLog::class, AttendanceLogPolicy::class)` في `AppServiceProvider`

#### `view(User $user, AttendanceLog $log): bool`
- **المنطق:**
  1. المستوى 10 / super_admin → `true`
  2. صاحب السجل (`user.id === log.user_id`) → `true`
  3. المدير المباشر لصاحب السجل → `true`
  4. المستوى 6+ مع نفس الفرع → `true`
  5. غير ذلك → `false`

#### `static scopeBranch(Builder $query, User $user): Builder`
- **الغرض:** تصفية الاستعلامات حسب فرع المستخدم
- **المنطق:** المستوى 10 → بدون تصفية; غيره → `WHERE branch_id = user.branch_id`
- **الاستخدام:** `AttendanceLogPolicy::scopeBranch($query, $user)`

---

## §25. مهمة تسجيل الحضور غير المتزامن (`App\Jobs\ProcessAttendanceJob`) — v4.0

**الملف:** `app/Jobs/ProcessAttendanceJob.php`
**السمات:** `ShouldQueue`, `SerializesModels`

| الخاصية | القيمة |
|---------|-------|
| timeout | 30 ثانية |
| tries | 3 محاولات |

#### `__construct(User $user, float $latitude, float $longitude, ?string $ip, ?string $device)`
- **الغرض:** إنشاء مهمة تسجيل حضور عبر الطابور

#### `handle(GeofencingService $geofencingService): void`
- **التسلسل:**
  1. جلب الفرع (إذا لم يوجد → تسجيل خطأ + إيقاف)
  2. التحقق من الموقع عبر `GeofencingService`
  3. جلب الوردية من `currentShift()` أو إعدادات الفرع
  4. إنشاء `AttendanceLog` + `evaluateAttendance()` + `calculateFinancials()`
  5. إطلاق حدث `AttendanceRecorded`

---

## §26. مهمة إرسال التعاميم (`App\Jobs\SendCircularJob`) — v4.0

**الملف:** `app/Jobs/SendCircularJob.php`
**السمات:** `ShouldQueue`, `SerializesModels`

| الخاصية | القيمة |
|---------|-------|
| timeout | 120 ثانية |
| tries | 2 محاولتان |

#### `__construct(Circular $circular, array $userIds)`

#### `handle(): void`
- **المنطق:** تقسيم المستخدمين إلى دفعات (100) → إنشاء `PerformanceAlert` لكل موظف
- **نوع التنبيه:** `circular`
- **الأهمية:** `warning` إذا التعميم عاجل، وإلا `info`
- **الحماية:** `sleep(1)` بين الدفعات + try/catch لكل موظف

---

## §27. الأحداث — Events (v4.0)

### `App\Events\BadgeAwarded`
- **السمات:** `Dispatchable`, `SerializesModels`
- **الإنشاء:** `new BadgeAwarded(UserBadge $userBadge)`
- **يُطلق من:** `UserBadge::award()` بعد منح الشارة

### `App\Events\TrapTriggered`
- **السمات:** `Dispatchable`, `SerializesModels`
- **الإنشاء:** `new TrapTriggered(TrapInteraction $interaction)`
- **يُطلق من:** TrapResponseService عند تسجيل تفاعل مع مصيدة

### `App\Events\AttendanceRecorded`
- **السمات:** `Dispatchable`, `SerializesModels`
- **الإنشاء:** `new AttendanceRecorded(AttendanceLog $log)`
- **يُطلق من:** `AttendanceService::checkIn()` + `ProcessAttendanceJob::handle()` بعد حفظ السجل

---

## §28. المستمعون — Listeners (v4.0)

### `App\Listeners\HandleBadgePoints`
- **يستمع إلى:** `BadgeAwarded`
- **handle():** يُنشئ `PerformanceAlert` بالتفاصيل:
  - `alert_type = 'badge_earned'`
  - `severity = 'success'`
  - `title_ar = 'تهانينا!'`
  - `trigger_data = {badge_id, user_badge_id, points_reward}`
- **الحماية:** try/catch + Log::warning

### `App\Listeners\LogTrapInteraction`
- **يستمع إلى:** `TrapTriggered`
- **handle():** يُسجل في `AuditLog::record()` بالتفاصيل:
  - `action = 'trap.triggered'`
  - `data = {trap_id, trap_type, trap_element, risk_level, ip_address, page_url}`
- **الحماية:** try/catch + Log::warning

---

## §29. استثناء الأعمال (`App\Exceptions\BusinessException`) — v4.0

**الملف:** `app/Exceptions/BusinessException.php`
**يرث من:** `Exception`

#### `__construct(string $userMessage, ?string $logMessage, int $httpCode = 422, array $context = [], ?Throwable $previous = null)`
- **الغرض:** إنشاء استثناء أعمال برسالة مستخدم ورسالة تسجيل منفصلة
- **$userMessage:** الرسالة التي تظهر للمستخدم النهائي
- **$logMessage:** الرسالة التقنية للسجلات (إن لم تُعطى → userMessage)
- **$httpCode:** كود HTTP (افتراضي 422)
- **$context:** بيانات إضافية للتدقيق

| الدالة | يُرجع |
|--------|--------|
| `getUserMessage()` | string — رسالة المستخدم |
| `getHttpCode()` | int — كود HTTP |
| `getContext()` | array — سياق إضافي |

---

## §30. استثناء خارج السياج (`App\Exceptions\OutOfGeofenceException`) — مُحدّث v4.0

**الملف:** `app/Exceptions/OutOfGeofenceException.php`
**يرث من:** `RuntimeException`

#### `__construct(float $distance, float $allowedRadius)`
- **الغرض:** يُطلق عندما يكون الموظف خارج نطاق السياج الجغرافي
- **الرسالة:** `__('attendance.outside_geofence', ['distance' => ..., 'radius' => ...])`

| الدالة | يُرجع | أُضيفت في |
|--------|--------|----------|
| `getDistance()` | float — المسافة الفعلية بالأمتار | v4.0 |
| `getAllowedRadius()` | float — نصف القطر المسموح | v4.0 |

---

## §31. متنبئ مغادرة الموظفين (`App\ML\ChurnPredictor`) — v4.0

**الملف:** `app/ML/ChurnPredictor.php`
**الغرض:** حساب درجة خطر مغادرة الموظف بناءً على أنماط الحضور

#### `calculateRisk(User $user): string`
- **يُرجع:** `'low'` | `'medium'` | `'high'` | `'critical'`
- **نطاق التحليل:** آخر 30 يوم
- **المؤشرات:** نسبة التأخر (0–30)، الغياب (0–30)، الانصراف المبكر (0–15)، قلة النقاط (0–15)
- **الحدود:** critical≥70, high≥45, medium≥20, low<20
- **لا بيانات:** يُرجع `'low'`

#### `getRiskDetails(User $user): array`
- **يُرجع:** `['user_id', 'risk_level', 'recommendation_ar', 'recommendation_en', 'analyzed_at']`
- **التوصيات:** حسب المستوى (كل مستوى له توصية بالعربية والإنجليزية)

---

## §32. مورد التنبيهات (`App\Filament\Resources\PerformanceAlertResource`) — v4.0

**الملف:** `app/Filament/Resources/PerformanceAlertResource.php`
**النموذج:** `PerformanceAlert`

| الخاصية | القيمة |
|---------|-------|
| الأيقونة | `heroicon-o-bell-alert` |
| المجموعة | مجموعة الموظفين |
| الترتيب | 15 |
| Badge | عدد غير المقروء (أصفر) |
| Branch Scope | غير المدير → فرعه فقط |

### أعمدة الجدول

| العمود | النوع | الوصف |
|--------|------|-------|
| `user.name_ar` | TextColumn | قابل للبحث + الترتيب |
| `alert_type` | Badge | لون حسب النوع |
| `severity` | Badge | لون حسب الأهمية |
| `title_ar` | Text | محدود بـ 50 حرف |
| `is_read` | Icon | boolean |
| `created_at` | DateTime | ترتيب تنازلي |

### الإجراءات
- **تحديد كمقروء:** فردي + جماعي → `is_read=true, read_at=now(), dismissed_by=auth.id`
- **عرض:** `ViewAction`

---

## §33. صفحة توثيق API (`App\Filament\Pages\ApiDocsPage`) — v4.0

**الملف:** `app/Filament/Pages/ApiDocsPage.php`
**القالب:** `resources/views/filament/pages/api-docs.blade.php`

| الخاصية | القيمة |
|---------|-------|
| الأيقونة | `heroicon-o-code-bracket` |
| المجموعة | الإعدادات |
| الترتيب | 99 |
| الصلاحية | `security_level >= 7 \|\| is_super_admin` |

### القالب (`api-docs.blade.php`)
- جدول نقاط النهاية (Endpoints) للحضور
- أمثلة الطلبات والاستجابات (JSON)
- جدول أكواد الأخطاء
- رابط إلى `https://sarh.online/docs/api` (Scramble)

---

## §34. تعديلات الخدمات الموجودة (v4.0)

### `AttendanceService::queueCheckIn(User $user, float $lat, float $lng, ?string $ip, ?string $device): void`
- **أُضيفت في:** v4.0
- **الغرض:** إرسال تسجيل الحضور إلى الطابور (غير متزامن)
- **المنطق:** `ProcessAttendanceJob::dispatch($user, $lat, $lng, $ip, $device)`

### `AttendanceController::queueCheckIn(Request $request): JsonResponse`
- **أُضيفت في:** v4.0
- **المسار:** `POST /attendance/queue-check-in`
- **الاستجابة:** HTTP 202 Accepted + `{message, job_status: 'queued'}`

### `RecalculateMonthlyAttendanceJob::forMonth(int $year, int $month): self`
- **أُضيفت في:** v4.0
- **الغرض:** إنشاء Job لإعادة حساب شهر كامل بنطاق `all`
- **تُستخدم من:** الجدولة الشهرية في `routes/console.php`

