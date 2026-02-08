# SARH — Methods Registry (Technical Registry)
> **Version:** 1.6.0 | **Updated:** 2026-02-09
> **Scope:** Documentation of every function, accessor, scope, constant, and mathematical formula

---

## Table of Contents

1. [User Model](#1-user-model-appmodelsuser)
2. [Branch Model](#2-branch-model-appmodelsbranch)
3. [AttendanceLog Model](#3-attendancelog-model-appmodelsattendancelog)
4. [FinancialReport Model](#4-financialreport-model-appmodelsfinancialreport)
5. [WhistleblowerReport Model](#5-whistleblowerreport-model-appmodelswhistleblowerreport)
6. [Role Model](#6-role-model-appmodelsrole)
7. [Permission Model](#7-permission-model-appmodelspermission)
8. [Department Model](#8-department-model-appmodelsdepartment)
9. [Conversation Model](#9-conversation-model-appmodelsconversation)
10. [Message Model](#10-message-model-appmodelsmessage)
11. [Circular Model](#11-circular-model-appmodelscircular)
12. [CircularAcknowledgment Model](#12-circularacknowledgment-model)
13. [PerformanceAlert Model](#13-performancealert-model)
14. [Badge Model](#14-badge-model-appmodelsbadge)
15. [PointsTransaction Model](#15-pointstransaction-model)
16. [TrapInteraction Model](#16-trapinteraction-model)
17. [LeaveRequest Model](#17-leaverequest-model)
18. [Shift Model](#18-shift-model-appmodelsshift)
19. [AuditLog Model](#19-auditlog-model-appmodelsauditlog)
20. [Holiday Model](#20-holiday-model-appmodelsholiday)

---

## 1. User Model (`App\Models\User`)

**File:** `app/Models/User.php`
**Table:** `users`
**Traits:** `HasFactory`, `Notifiable`, `SoftDeletes`

### 1.1 Computed Accessors (Financial Engine)

#### `getTotalSalaryAttribute(): float`
- **Purpose:** Calculate total monthly compensation
- **Formula:** `basic_salary + housing_allowance + transport_allowance + other_allowances`
- **Returns:** `float` — Total monthly compensation in SAR
- **Usage:** `$user->total_salary`

#### `getMonthlyWorkingMinutesAttribute(): int`
- **Purpose:** Calculate total working minutes per month
- **Formula:** `working_days_per_month × working_hours_per_day × 60`
- **Returns:** `int` — Total available working minutes in a month
- **Usage:** `$user->monthly_working_minutes`
- **Example:** `22 × 8 × 60 = 10,560 minutes`

#### `getCostPerMinuteAttribute(): float`
- **Purpose:** **Core financial metric** — the cost of each minute of employee delay
- **Formula:** `basic_salary ÷ monthly_working_minutes`
- **Mathematical Proof:**
  ```
  cost_per_minute = basic_salary / (working_days_per_month × working_hours_per_day × 60)

  For salary = 8000 SAR, 22 days, 8 hours:
  monthly_working_minutes = 22 × 8 × 60 = 10,560
  cost_per_minute = 8000 / 10,560 = 0.7576 SAR/min (rounded to 4 decimals)
  ```
- **Guard:** Returns `0.0` if `monthly_working_minutes <= 0` (prevents division by zero)
- **Returns:** `float` — Rounded to 4 decimal places
- **Usage:** `$user->cost_per_minute`

#### `getTotalCostPerMinuteAttribute(): float`
- **Purpose:** Same as above but uses **total compensation** not just basic salary
- **Formula:** `total_salary ÷ monthly_working_minutes`
- **Returns:** `float` — Rounded to 4 decimal places
- **Usage:** `$user->total_cost_per_minute`
- **Note:** Used for comprehensive reporting; `cost_per_minute` (basic only) is the standard for delay cost

#### `getDailyRateAttribute(): float`
- **Purpose:** Calculate the daily salary rate (used for leave cost estimation)
- **Formula:** `basic_salary ÷ working_days_per_month`
- **Guard:** Returns `0.0` if `working_days_per_month <= 0`
- **Returns:** `float` — Rounded to 2 decimal places
- **Usage:** `$user->daily_rate`

### 1.2 Computed Accessors (Localization)

#### `getNameAttribute(): string`
- **Purpose:** Return localized name based on app locale
- **Logic:** `app()->getLocale() === 'ar' ? name_ar : name_en`
- **Usage:** `$user->name`

#### `getJobTitleAttribute(): ?string`
- **Purpose:** Return localized job title
- **Logic:** Same pattern as `getNameAttribute`
- **Usage:** `$user->job_title`

### 1.3 Financial Methods

#### `calculateDelayCost(int $minutes): float`
- **Purpose:** Calculate the financial cost of a given number of delay minutes
- **Formula:** `$minutes × cost_per_minute`
- **Parameters:** `$minutes` — Number of minutes of delay
- **Returns:** `float` — Rounded to 2 decimal places
- **Example:** `$user->calculateDelayCost(15)` → `15 × 0.7576 = 11.36 SAR`

### 1.4 RBAC Methods

#### `hasPermission(string $slug): bool`
- **Purpose:** Check if user has a specific permission through their role
- **Logic:** `is_super_admin → true` (bypass), otherwise checks `role.permissions` for matching slug
- **Parameters:** `$slug` — Permission identifier (e.g., `'finance.view_all'`)
- **Returns:** `bool`

#### `hasSecurityLevel(int $minimumLevel): bool`
- **Purpose:** Check if user's security level meets minimum requirement
- **Logic:** `security_level >= $minimumLevel`
- **Parameters:** `$minimumLevel` — Integer 1-10

#### `canManage(User $target): bool`
- **Purpose:** Determine if current user can manage target user
- **Logic:** `is_super_admin → true`, otherwise `security_level > target.security_level`
- **Design:** Uses strict `>` (not `>=`) — peers cannot manage each other

### 1.5 Security Methods (Bypass $fillable)

#### `setSecurityLevel(int $level): self`
- **Purpose:** Set user's security level (NOT mass-assignable)
- **Logic:** Uses `forceFill()` to bypass `$fillable`. Clamps value to 1-10 range via `max(1, min($level, 10))`
- **Returns:** `self` (chainable)

#### `promoteToSuperAdmin(): self`
- **Purpose:** Grant super admin privileges
- **Logic:** Sets `is_super_admin = true` and `security_level = 10` via `forceFill()`
- **Returns:** `self` (chainable)

#### `enableTrapMonitoring(): self`
- **Purpose:** Flag user for psychological trap monitoring
- **Logic:** Sets `is_trap_target = true` via `forceFill()`
- **Returns:** `self` (chainable)

#### `recordLogin(string $ip): void`
- **Purpose:** Record successful login (security audit trail)
- **Logic:** Updates `last_login_at`, `last_login_ip`, resets `failed_login_attempts` and `locked_until`

### 1.6 Static Methods

#### `generateEmployeeId(): string`
- **Purpose:** Auto-generate unique employee badge number
- **Format:** `SARH-{YY}-{0001}` (e.g., `SARH-26-0042`)
- **Logic:** Counts all users (including soft-deleted) + 1, pads to 4 digits
- **Called:** Automatically in `booted()` via `creating` event

### 1.7 Query Scopes

| Scope | Signature | SQL Effect |
|-------|-----------|------------|
| `scopeActive` | `($query)` | `WHERE status = 'active'` |
| `scopeInBranch` | `($query, int $branchId)` | `WHERE branch_id = ?` |
| `scopeInDepartment` | `($query, int $departmentId)` | `WHERE department_id = ?` |
| `scopeWithSecurityLevel` | `($query, int $minLevel)` | `WHERE security_level >= ?` |

### 1.8 Relationships

| Method | Type | Related Model | FK/Pivot |
|--------|------|---------------|----------|
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
| `badges()` | `BelongsToMany` | `Badge` | `user_badges` |
| `pointsTransactions()` | `HasMany` | `PointsTransaction` | `user_id` |
| `trapInteractions()` | `HasMany` | `TrapInteraction` | `user_id` |
| `shifts()` | `BelongsToMany` | `Shift` | `user_shifts` |
| `currentShift()` | Method (not relation) | `Shift` | Pivot `is_current = true` |

### 1.9 Protected Fields (NOT in `$fillable`)

| Field | Why Protected | Setter Method |
|-------|--------------|---------------|
| `is_super_admin` | Ultimate system privilege | `promoteToSuperAdmin()` |
| `security_level` | RBAC level — cascading permissions | `setSecurityLevel(int)` |
| `is_trap_target` | Covert integrity monitoring | `enableTrapMonitoring()` |
| `last_login_at` | System-managed | `recordLogin(string)` |
| `last_login_ip` | System-managed | `recordLogin(string)` |
| `failed_login_attempts` | System-managed | `recordLogin(string)` |
| `locked_until` | System-managed | `recordLogin(string)` |

---

## 2. Branch Model (`App\Models\Branch`)

**File:** `app/Models/Branch.php`
**Table:** `branches`

### 2.1 Geofencing Methods

#### `distanceTo(float $lat, float $lng): float`
- **Purpose:** Calculate distance in meters between branch center and given coordinates
- **Algorithm:** Haversine Formula
- **Mathematical Proof:**
  ```
  Earth radius (R) = 6,371,000 meters

  a = sin²(Δlat/2) + cos(lat₁) × cos(lat₂) × sin²(Δlng/2)
  c = 2 × atan2(√a, √(1-a))
  distance = R × c
  ```
- **Parameters:** `$lat`, `$lng` — GPS coordinates of the employee
- **Returns:** `float` — Distance in meters, rounded to 2 decimal places

#### `isWithinGeofence(float $lat, float $lng): bool`
- **Purpose:** Check if coordinates are within the branch's geofence
- **Logic:** `distanceTo(lat, lng) <= geofence_radius`
- **Default radius:** 17 meters (configurable per branch)

### 2.2 Financial Methods

#### `recalculateSalaryBudget(): void`
- **Purpose:** Refresh cached `monthly_salary_budget` from active employees
- **Logic:** `SUM(basic_salary) WHERE branch_id = this AND status = 'active'`
- **Called:** Manually or via scheduled job

### 2.3 Relationships

| Method | Type | Related Model |
|--------|------|---------------|
| `users()` | `HasMany` | `User` |
| `departments()` | `HasMany` | `Department` |
| `attendanceLogs()` | `HasMany` | `AttendanceLog` |
| `financialReports()` | `HasMany` | `FinancialReport` |
| `holidays()` | `HasMany` | `Holiday` |

---

## 3. AttendanceLog Model (`App\Models\AttendanceLog`)

**File:** `app/Models/AttendanceLog.php`
**Table:** `attendance_logs`

### 3.1 Financial Methods

#### `calculateFinancials(): self`
- **Purpose:** Snapshot the employee's financial rate and compute all costs
- **Logic:**
  ```php
  cost_per_minute  = user.cost_per_minute  // Snapshot from accessor
  delay_cost       = delay_minutes × cost_per_minute
  early_leave_cost = early_leave_minutes × cost_per_minute
  overtime_value   = overtime_minutes × cost_per_minute × 1.5  // 1.5x rate
  ```
- **Returns:** `self` (chainable — call `->save()` after)
- **Critical:** Must be called at check-in to snapshot the current salary rate

#### `evaluateAttendance(string $shiftStart, int $gracePeriod = 5): self`
- **Purpose:** Determine attendance status and delay minutes
- **Logic:**
  ```
  IF no check_in_at → status = 'absent'
  ELSE IF check_in_at ≤ (shift_start + grace_period) → status = 'present', delay = 0
  ELSE → status = 'late', delay_minutes = diff(check_in_at, shift_start)
  ```
- **Parameters:**
  - `$shiftStart` — Time string e.g., `'08:00'`
  - `$gracePeriod` — Grace minutes (default: 5)

### 3.2 Query Scopes

| Scope | Effect |
|-------|--------|
| `scopeForDate($query, $date)` | `WHERE attendance_date = ?` |
| `scopeLate($query)` | `WHERE status = 'late'` |
| `scopeAbsent($query)` | `WHERE status = 'absent'` |
| `scopeWithDelayCost($query)` | `WHERE delay_cost > 0` |
| `scopeTotalDelayCost($query)` | Returns `SUM(delay_cost)` as float |

### 3.3 Relationships

| Method | Type | Related Model |
|--------|------|---------------|
| `user()` | `BelongsTo` | `User` |
| `branch()` | `BelongsTo` | `Branch` |
| `approvedByUser()` | `BelongsTo` | `User` (via `approved_by`) |

---

## 4. FinancialReport Model (`App\Models\FinancialReport`)

**File:** `app/Models/FinancialReport.php`
**Table:** `financial_reports`

### 4.1 Report Generation

#### `static generateForEmployee(User $user, string $start, string $end): self`
- **Purpose:** Build a complete financial report for one employee over a date range
- **Logic:**
  1. Query all `AttendanceLogs` for user in date range
  2. Aggregate: counts by status, sums of minutes and costs
  3. Calculate `net_financial_impact = delay_cost + early_leave_cost - overtime_cost`
  4. Calculate `loss_percentage = (total_delay_cost / total_salary_budget) × 100`
- **Returns:** Unsaved `FinancialReport` instance (call `->save()` to persist)
- **Note:** `report_code` auto-generated as `FIN-EMP-{employee_id}-{timestamp}`

#### `static generateReportCode(string $scope): string`
- **Purpose:** Generate unique report identifier
- **Format:** `FIN-{SCOPE_PREFIX}-{YmdHis}-{random_3_digits}`
- **Example:** `FIN-BRA-20260207143022-042`

### 4.2 Query Scopes

| Scope | Effect |
|-------|--------|
| `scopeForPeriod($query, $start, $end)` | `WHERE period_start BETWEEN ? AND ?` |
| `scopeByScope($query, $scope)` | `WHERE scope = ?` |

### 4.3 Relationships

| Method | Type | Related Model |
|--------|------|---------------|
| `user()` | `BelongsTo` | `User` |
| `branch()` | `BelongsTo` | `Branch` |
| `department()` | `BelongsTo` | `Department` |
| `generatedByUser()` | `BelongsTo` | `User` (via `generated_by`) |

---

## 5. WhistleblowerReport Model (`App\Models\WhistleblowerReport`)

**File:** `app/Models/WhistleblowerReport.php`

### 5.1 Encryption Methods

#### `setContent(string $plainText): self`
- **Purpose:** Encrypt and store report body
- **Logic:** `encrypted_content = encrypt($plainText)` (Laravel AES-256-CBC)

#### `getContent(): string`
- **Purpose:** Decrypt and return report body
- **Logic:** `decrypt($this->encrypted_content)`

### 5.2 Static Methods

#### `static generateTicketNumber(): string`
- **Format:** `WB-{8_hex_chars}-{yymmdd}`
- **Example:** `WB-A3F1B2C4-260207`

#### `static generateAnonymousToken(): string`
- **Algorithm:** `SHA-256(random_bytes(32) + microtime)`
- **Purpose:** Hashed token for anonymous follow-up

---

## 6. Role Model (`App\Models\Role`)

### 6.1 Methods

| Method | Signature | Purpose |
|--------|-----------|---------|
| `grantPermission` | `(Permission $permission): void` | Add permission via `syncWithoutDetaching` |
| `revokePermission` | `(Permission $permission): void` | Remove permission via `detach` |
| `hasPermission` | `(string $slug): bool` | Check if role has a specific permission |

---

## 7. Permission Model (`App\Models\Permission`)

### 7.1 Scopes

| Scope | Effect |
|-------|--------|
| `scopeInGroup($query, string $group)` | `WHERE group = ?` |

---

## 8. Department Model (`App\Models\Department`)

### 8.1 Relationships

| Method | Type | Related Model |
|--------|------|---------------|
| `branch()` | `BelongsTo` | `Branch` |
| `parent()` | `BelongsTo` | `Department` (self) |
| `children()` | `HasMany` | `Department` (self) |
| `head()` | `BelongsTo` | `User` (via `head_id`) |
| `users()` | `HasMany` | `User` |
| `financialReports()` | `HasMany` | `FinancialReport` |

---

## 9-12. Messaging Models

### Conversation

| Method | Type | Related |
|--------|------|---------|
| `creator()` | `BelongsTo` | `User` (via `created_by`) |
| `participants()` | `BelongsToMany` | `User` (pivot: `conversation_participants`) |
| `messages()` | `HasMany` | `Message` |
| `latestMessage()` | `HasOne` | `Message` (`latestOfMany`) |

### Message
| Method | Type | Related |
|--------|------|---------|
| `conversation()` | `BelongsTo` | `Conversation` |
| `sender()` | `BelongsTo` | `User` (via `sender_id`) |

### Circular
| Method | Type | Related |
|--------|------|---------|
| `creator()` | `BelongsTo` | `User` (via `created_by`) |
| `targetBranch()` | `BelongsTo` | `Branch` |
| `targetDepartment()` | `BelongsTo` | `Department` |
| `targetRole()` | `BelongsTo` | `Role` |
| `acknowledgments()` | `HasMany` | `CircularAcknowledgment` |

**Scopes:** `scopePublished`, `scopeActive` (published + not expired)

---

## 13. PerformanceAlert Model

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeUnread($query)` | `WHERE is_read = false` |
| `scopeCritical($query)` | `WHERE severity = 'critical'` |

---

## 14. Badge Model (`App\Models\Badge`)

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeActive($query)` | `WHERE is_active = true` |
| `scopeByCategory($query, string $category)` | `WHERE category = ?` |

---

## 15. PointsTransaction Model

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeEarned($query)` | `WHERE type = 'earned'` |
| `scopeDeducted($query)` | `WHERE type = 'deducted'` |

### Polymorphic Relationship
- `sourceable()` — `MorphTo` — Any model can be the source of points

---

## 16. TrapInteraction Model

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeUnreviewed($query)` | `WHERE is_reviewed = false` |
| `scopeHighRisk($query)` | `WHERE risk_level IN ('high', 'critical')` |

---

## 17. LeaveRequest Model

#### `calculateCost(): self`
- **Purpose:** Estimate cost of leave based on daily rate
- **Formula:** `total_days × user.daily_rate`

### Scopes
| Scope | Effect |
|-------|--------|
| `scopePending($query)` | `WHERE status = 'pending'` |
| `scopeApproved($query)` | `WHERE status = 'approved'` |

---

## 18. Shift Model (`App\Models\Shift`)

#### `getDurationMinutesAttribute(): int`
- **Purpose:** Calculate shift duration in minutes
- **Logic:** Handles overnight shifts (end < start → add 24h)
- **Usage:** `$shift->duration_minutes`

---

## 19. AuditLog Model (`App\Models\AuditLog`)

#### `static record(string $action, ?Model $model, ?array $old, ?array $new, ?string $description): self`
- **Purpose:** Quick logging helper — captures user, IP, user-agent automatically
- **Usage:** `AuditLog::record('update', $user, $oldData, $newData, 'Changed salary')`

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeForModel($query, string $type, int $id)` | Filter by polymorphic target |
| `scopeByAction($query, string $action)` | `WHERE action = ?` |

---

## 20. Holiday Model (`App\Models\Holiday`)

#### `static isHoliday(Carbon $date, ?int $branchId = null): bool`
- **Purpose:** Check if a date is a holiday (globally or for a specific branch)
- **Logic:** Checks `WHERE date = ? AND (branch_id IS NULL OR branch_id = ?)`

---

## 21. GeofencingService (`App\Services\GeofencingService`)

**File:** `app/Services/GeofencingService.php`

#### `validatePosition(Branch $branch, float $lat, float $lng): array`
- **Purpose:** Validate GPS coordinates against a branch's geofence
- **Returns:** `['distance_meters' => float, 'within_geofence' => bool]`
- **Logic:** Delegates to `Branch::distanceTo()` and `Branch::isWithinGeofence()`
- **Example:** `$result = $service->validatePosition($branch, 24.7136, 46.6753)`

#### `static haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float`
- **Purpose:** Static utility for Haversine distance (meters)
- **Algorithm:** Same as `Branch::distanceTo()` but stateless
- **Returns:** `float` — meters, rounded to 2 decimals

---

## 22. AttendanceService (`App\Services\AttendanceService`)

**File:** `app/Services/AttendanceService.php`

#### `checkIn(User $user, float $lat, float $lng, ?string $ip, ?string $device): AttendanceLog`
- **Purpose:** Full check-in flow with geofencing + financial snapshot
- **Steps:**
  1. Load user's branch (`$user->branch`)
  2. Validate geofence via `GeofencingService::validatePosition()`
  3. Reject if outside geofence (throws `OutOfGeofenceException`)
  4. Resolve shift via `$user->currentShift()` or branch defaults
  5. Create `AttendanceLog` with GPS data
  6. Call `evaluateAttendance(shift_start, grace_period)` → sets status + delay
  7. Call `calculateFinancials()` → snapshots cost_per_minute + computes costs
  8. Save and return
- **Throws:** `OutOfGeofenceException` if `within_geofence === false`
- **Returns:** Saved `AttendanceLog` instance

#### `checkOut(User $user, float $lat, float $lng): AttendanceLog`
- **Purpose:** Full check-out flow with overtime/early-leave calculation
- **Steps:**
  1. Find today's `AttendanceLog` for user
  2. Validate geofence for check-out coordinates
  3. Calculate `worked_minutes` from check-in/check-out diff
  4. Compare with shift duration → overtime or early_leave
  5. Recalculate financials
  6. Save and return
- **Throws:** `ModelNotFoundException` if no check-in for today
- **Returns:** Updated `AttendanceLog` instance

#### `calculateDelayCost(User $user, int $minutesDelayed): float`
- **Purpose:** Wrapper for `User::calculateDelayCost()` — available as service method
- **Formula:** `(basic_salary / working_days / working_hours / 60) × delay_minutes`
- **Returns:** `float` — rounded to 2 decimals

---

## 23. AttendanceController (`App\Http\Controllers\AttendanceController`)

**File:** `app/Http/Controllers/AttendanceController.php`

#### `checkIn(Request $request): JsonResponse`
- **Route:** `POST /attendance/check-in`
- **Validation:** `latitude` (required, numeric, -90..90), `longitude` (required, numeric, -180..180)
- **Auth:** Authenticated user (middleware `auth`)
- **Returns:** `201` with AttendanceLog JSON or `422` if outside geofence

#### `checkOut(Request $request): JsonResponse`
- **Route:** `POST /attendance/check-out`
- **Validation:** Same GPS fields
- **Returns:** `200` with updated AttendanceLog JSON

#### `todayStatus(Request $request): JsonResponse`
- **Route:** `GET /attendance/today`
- **Returns:** Today's AttendanceLog or `null` if not checked in

---

## 24. Trap Model (`App\Models\Trap`)

**File:** `app/Models/Trap.php`
**Table:** `traps`

#### `getNameAttribute(): string`
- **Purpose:** Return localized trap name
- **Logic:** `app()->getLocale() === 'ar' ? name_ar : name_en`

#### `deriveRiskLevel(): string`
- **Purpose:** Map `risk_weight` (1-10) to human-readable risk level
- **Logic:**
  ```
  1-3  → 'low'
  4-6  → 'medium'
  7-8  → 'high'
  9-10 → 'critical'
  ```
- **Returns:** `string` — one of `low`, `medium`, `high`, `critical`

### Scopes
| Scope | Effect |
|-------|--------|
| `scopeActive($query)` | `WHERE is_active = true` |
| `scopeByCode($query, string $code)` | `WHERE trap_code = ?` |

### Relationships
| Method | Type | Related Model |
|--------|------|---------------|
| `interactions()` | `HasMany` | `TrapInteraction` |

---

## 25. User Model — Trap Risk Extensions

### Risk Scoring Methods

#### `incrementRiskScore(): int`
- **Purpose:** Calculate and persist logarithmic risk score after a trap interaction
- **Formula:** `risk_score = 10 × (2^n − 1)` where `n` = total trap interaction count
- **Mathematical Proof:**
  ```
  n=1: 10 × (2¹ − 1) =   10
  n=2: 10 × (2² − 1) =   30
  n=3: 10 × (2³ − 1) =   70
  n=4: 10 × (2⁴ − 1) =  150
  n=5: 10 × (2⁵ − 1) =  310
  ```
- **Persistence:** Uses `forceFill()` — `risk_score` is NOT in `$fillable`
- **Returns:** `int` — The new risk score

#### `getRiskLevelAttribute(): string`
- **Purpose:** Human-readable risk category based on cumulative score
- **Logic:**
  ```
  score < 30   → 'low'
  score < 100  → 'medium'
  score < 300  → 'high'
  score >= 300 → 'critical'
  ```

---

## 26. TrapResponseService (`App\Services\TrapResponseService`)

**File:** `app/Services/TrapResponseService.php`

#### `triggerTrap(User $user, string $trapCode, Request $request): array`
- **Purpose:** Full trap trigger flow: log interaction → increment risk → generate fake response
- **Steps:**
  1. Resolve `Trap` by `trap_code`
  2. Create `TrapInteraction` record with IP, user-agent, metadata
  3. Call `User::incrementRiskScore()`
  4. Call `generateFakeResponse()` for the trap type
- **Returns:** `array` — Fake response payload for the PWA to display
- **Throws:** `ModelNotFoundException` if trap_code invalid

#### `generateFakeResponse(Trap $trap): array`
- **Purpose:** Produce convincing fake UI feedback per trap type
- **Returns:** Keyed array with trap-specific response:
  ```php
  // SALARY_PEEK
  ['type' => 'table', 'data' => [...fake salary rows...]]

  // PRIVILEGE_ESCALATION
  ['type' => 'success', 'message' => 'Temporary admin access granted']

  // SYSTEM_BYPASS
  ['type' => 'warning', 'message' => 'Attendance system paused for 24h']

  // DATA_EXPORT
  ['type' => 'download', 'progress' => 100, 'url' => '/exports/fake_...csv']
  ```

---

## 27. TrapController (`App\Http\Controllers\TrapController`)

**File:** `app/Http/Controllers/TrapController.php`

#### `trigger(Request $request): JsonResponse`
- **Route:** `POST /traps/trigger`
- **Validation:** `trap_code` (required, exists:traps,trap_code)
- **Auth:** Authenticated user
- **Returns:** `200` with fake response payload

---

## 28. Livewire Components — Employee PWA

### EmployeeDashboard (`App\Livewire\EmployeeDashboard`)
- **Mount:** Loads authenticated user with relationships
- **View:** `livewire.employee-dashboard` — Container for all 4 widgets

### AttendanceWidget (`App\Livewire\AttendanceWidget`)
- **Purpose:** Show today's attendance status with GPS check-in/out
- **Properties:** `$status`, `$checkInTime`, `$checkOutTime`
- **Methods:**
  - `checkIn()` — Calls AttendanceService with geolocation
  - `checkOut()` — Calls AttendanceService for check-out

### GamificationWidget (`App\Livewire\GamificationWidget`)
- **Purpose:** Display points, current streak, and earned badges
- **Properties:** `$totalPoints`, `$currentStreak`, `$badges`

### FinancialWidget (`App\Livewire\FinancialWidget`)
- **Purpose:** Show "My Discipline Score" — delay cost impact
- **Properties:** `$delayCost`, `$onTimeRate`, `$monthlyLogs`

### CircularsWidget (`App\Livewire\CircularsWidget`)
- **Purpose:** List active circulars with acknowledgment status
- **Methods:**
  - `acknowledge(int $circularId)` — Creates CircularAcknowledgment record

---

## 29. Livewire Components — Whistleblower System

### WhistleblowerForm (`App\Livewire\WhistleblowerForm`)
- **Purpose:** Anonymous encrypted report submission
- **Properties:** `$category`, `$severity`, `$content`
- **Methods:**
  - `submit()` — Encrypts content, generates ticket + token, stores report
  - Returns ticket_number + anonymous_token (shown ONCE)
- **Security:** No authentication required. No user FK stored.

### WhistleblowerTrack (`App\Livewire\WhistleblowerTrack`)
- **Purpose:** Track report status by anonymous token
- **Properties:** `$token`, `$report`
- **Methods:**
  - `track()` — Finds report by anonymous_token, shows status (no content)

---

## 30. Livewire Components — Messaging

### MessagingInbox (`App\Livewire\MessagingInbox`)
- **Purpose:** List all conversations with latest message preview
- **Properties:** `$conversations`, `$unreadCount`
- **Polling:** 5-second refresh for new messages

### MessagingChat (`App\Livewire\MessagingChat`)
- **Purpose:** Single conversation view with message bubbles
- **Properties:** `$conversation`, `$messages`, `$newMessage`
- **Methods:**
  - `sendMessage()` — Creates Message record, marks sender's as read
  - `markAsRead()` — Updates read status on mount + refresh
- **Polling:** 3-second refresh for new messages

---

## 31. PWA Controllers & Routes

### DashboardController (`App\Http\Controllers\DashboardController`)
- **Route:** `GET /dashboard` → Employee dashboard
- **Auth:** Required (middleware `auth`)

### WhistleblowerController (`App\Http\Controllers\WhistleblowerController`)
- **Routes:**
  - `GET /whistleblower` → Anonymous report form (NO auth)
  - `GET /whistleblower/track` → Track report by token (NO auth)

### MessagingController (`App\Http\Controllers\MessagingController`)
- **Routes:**
  - `GET /messaging` → Inbox (auth required)
  - `GET /messaging/{conversation}` → Chat view (auth required)

---

---

## §32. FinancialReportingService (Phase 4)

| Method | Signature | Returns | Description |
|--------|-----------|---------|-------------|
| `getDailyLoss` | `(Carbon $date, ?int $branchId): float` | float | Total delay cost across branches for a given date |
| `getBranchPerformance` | `(Carbon $month): Collection` | Collection | Per-branch stats: total_employees, on_time_rate, geofence_compliance, total_loss |
| `getDelayImpactAnalysis` | `(string $start, string $end, string $scope, ?int $scopeId): array` | array | ROI analysis: potential_loss, actual_loss, roi_percentage, discipline_savings |
| `getPredictiveMonthlyLoss` | `(Carbon $month): array` | array | Predictive forecast: avg_daily_loss, accumulated_loss, remaining_days, predicted_total |

---

## §33. Filament Dashboard Widgets (Phase 4)

| Widget | Parent Class | Key Methods |
|--------|-------------|-------------|
| `RealTimeLossCounter` | StatsOverviewWidget | `getStats()` — today's loss, late count, absent count, loss trend |
| `BranchPerformanceHeatmap` | TableWidget | `table()` — branch rows with on_time_rate, compliance, loss columns, color-coded |
| `IntegrityAlertHub` | TableWidget | `table()` — recent trap triggers + whistleblower statuses (Level 10 gate) |

---

## §34. Level 10 Vault Pages (Phase 4)

| Page | Route | Methods |
|------|-------|---------|
| `WhistleblowerVaultPage` | `/admin/whistleblower-vault` | `table()` — decrypted report viewing; `viewReport()` — audit-logged decryption |
| `TrapAuditPage` | `/admin/trap-audit` | `table()` — full interaction audit trail; risk trajectory data |

---

## Changelog

| Date | Version | Changes |
|------|---------|--------|
| 2026-02-07 | 1.0.0 | Initial registry — 20 models, 50+ methods documented |
| 2026-02-07 | 1.1.0 | Phase 1 — GeofencingService (2 methods), AttendanceService (3 methods), AttendanceController (3 endpoints) |
| 2026-02-07 | 1.2.0 | Phase 2 — Trap model (2 methods, 2 scopes), User risk extensions (2 methods), TrapResponseService (2 methods), TrapController (1 endpoint) |
| 2026-02-07 | 1.3.0 | Phase 3 — 8 Livewire components, 3 controllers, 6 routes, PWA layout, whistleblower encryption flow, messaging with read receipts |
| 2026-02-08 | 1.4.0 | Phase 4 — FinancialReportingService (4 methods), 3 dashboard widgets, 2 Level-10 vault pages, predictive analytics algorithm |
| 2026-02-08 | 1.5.0 | Phase 5 (Final) — SarhInstallCommand, BranchScope policy, financial caching layer, performance indexes, bilingual hardening |

---

## §35. Production Hardening (Phase 5 — Final)

### SarhInstallCommand (`App\Console\Commands\SarhInstallCommand`)
- **Signature:** `sarh:install`
- **Purpose:** One-command installation — seeds RBAC, badges, traps, creates initial Super Admin
- **Steps:**
  1. `verifyEnvironment()` — Checks PHP version, extensions, APP_KEY, DB connection
  2. `runMigrations()` — Runs `php artisan migrate --force`
  3. `seedCoreData()` — Calls RolesAndPermissionsSeeder, BadgesSeeder, TrapsSeeder
  4. `createSuperAdmin()` — Interactive prompts for name_ar, name_en, email, password → creates user with Level 10
  5. `finalizeInstallation()` — `storage:link`, `config:cache`, `route:cache`

### FinancialReportingService — Caching Layer
- **Cache TTL:** 300 seconds (5 minutes)
- **Cached:** `getDailyLoss`, `getBranchPerformance`, `getPredictiveMonthlyLoss`
- **Non-cached:** `getDelayImpactAnalysis` (on-demand, user-triggered)
- **Key format:** `sarh.{method}.{date/month}.{branch_id?}`

### BranchScope Security Policy
- **Applied in:** AttendanceLogResource `getEloquentQuery()`
- **Logic:** Non-super-admin sees only their `branch_id` data
- **Super Admin:** No scope restriction

### Performance Indexes (Migration)
- **Table:** `attendance_logs` — 3 new indexes (delay_cost, user_id+status, attendance_date+delay_cost)
- **Table:** `trap_interactions` — 3 new indexes (trap_id, created_at, user_id+created_at)
- **Table:** `audit_logs` — 2 new indexes (user_id, action)

### Bilingual Additions
- **File:** `lang/{ar,en}/install.php` — 15+ keys for installation command output

---

## §36. UI/UX Overhaul — Resource Architecture (v1.6.0)

### UserResource (`App\Filament\Resources\UserResource`)

| Method | Purpose |
|--------|---------|
| `getEloquentQuery()` | Branch scope — non-super-admin sees only their branch employees |
| `form()` | Core Four schema: avatar (required), name_ar/en, email, password, basic_salary + collapsible organizational section |
| `table()` | Avatar column, employee_id badge, name, email, branch, role, salary, security_level color-coded badge, status icon |
| Hidden defaults | `working_days_per_month=22`, `working_hours_per_day=8`, `locale=ar`, `timezone=Asia/Riyadh` |

### BranchResource (`App\Filament\Resources\BranchResource`)

| Method | Purpose |
|--------|---------|
| `form()` | Identity section + Leaflet.js map picker (ViewField) + lat/lng/radius fields + collapsible shift/address/financial sections |
| `table()` | Code badge, names, city, geofence_radius, shift times, grace period, employees_count, is_active icon |
| Map Picker | `filament.forms.components.map-picker` — Alpine.js + Leaflet.js with bidirectional lat/lng/radius binding |

### Map Picker Component (`resources/views/filament/forms/components/map-picker.blade.php`)

| Feature | Implementation |
|---------|-----------|
| Map Engine | Leaflet.js v1.9.4 with OpenStreetMap tiles |
| Marker | Draggable, updates lat/lng on dragend |
| Click Handler | Map click places marker + updates coordinates |
| Geofence Circle | Orange (#f97316), 15% opacity, radius synced with form field |
| Watchers | `$watch('radius')`, `$watch('lat')`, `$watch('lng')` — bidirectional sync |
| Default Center | Riyadh: 24.7136, 46.6753 |

### AppServiceProvider — Level 10 Gates

| Gate | Condition | Effect |
|------|-----------|--------|
| `Gate::before()` | `security_level === 10 \|\| is_super_admin` | Returns `true` for ALL authorization checks |
| `access-whistleblower-vault` | `security_level >= 10` | Vault page access |
| `access-trap-audit` | `security_level >= 10` | Trap audit page access |
| `bypass-geofence` | `security_level >= 10 \|\| is_super_admin` | Attendance check-in from any location |

### Bilingual Lang Files Added
- **File:** `lang/{ar,en}/users.php` — 30+ keys for employee management UI
- **File:** `lang/{ar,en}/branches.php` — 30+ keys for branch management UI

---

## §37. Competition Engine — Branch Leaderboard & News Ticker (v1.7.0)

### ProjectDataSeeder (`Database\Seeders\ProjectDataSeeder`)

| Method | Purpose |
|--------|---------|
| `run()` | Seeds 5 real Saudi branches (GPS coordinates + geofence) + 42 employees with `updateOrCreate` for idempotency |
| Branch distribution | RUH-HQ: 12, JED-01: 10, DMM-01: 8, MED-01: 6, ABH-01: 5 |
| Default password | `Goolbx512!!` for all seeded users |
| Super admin | `abdullah@sarh.app` — security_level=10, total_points=500 |

### BranchLeaderboardPage (`App\Filament\Pages\BranchLeaderboardPage`)

| Method | Purpose |
|--------|---------|
| `getBranches()` | Calculates discipline score per active branch: base 100, -2/late, -5/missed, +10/perfect employee, +0.1×points. Returns ranked array with 6-tier level assignment |
| Level tiers | Legendary (≥150), Diamond (≥120), Gold (≥100), Silver (≥80), Bronze (≥60), Starter (<60) |
| Trophy/Turtle | Rank #1 gets 🏆, last place gets 🐢 |

### DailyNewsTicker (`App\Filament\Widgets\DailyNewsTicker`)

| Method | Purpose |
|--------|---------|
| `getNewsItems()` | Aggregates today's competition news: best/worst branch, attendance stats, top scorer, total employees |
| `getBestBranchToday()` | Branch with lowest avg delay_minutes today |
| `getWorstBranchToday()` | Branch with highest avg delay_minutes today |

### UserResource Points Action

| Method | Purpose |
|--------|---------|
| `adjust_points` action | Filament table action — Level 10 enters points + reason → `total_points` increment + `points_transactions` insert + toast notification |
| Gate | `adjust-points` — requires security_level ≥ 10 or is_super_admin |

### AppServiceProvider — Competition Gates (v1.7.0)

| Gate | Condition | Effect |
|------|-----------|--------|
| `manage-competition` | `security_level >= 10 \|\| is_super_admin` | Competition page management |
| `adjust-points` | `security_level >= 10 \|\| is_super_admin` | Manual points adjustment |

### Bilingual Lang Files Added (v1.7.0)
- **File:** `lang/{ar,en}/competition.php` — 30+ keys for competition UI (leaderboard, levels, ticker, scoring)
- **File:** `lang/{ar,en}/users.php` — 7 new points management keys added
