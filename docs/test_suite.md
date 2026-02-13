# SARH — Validation Protocol (Test Suite)
> **Version:** 4.0.0 | **Updated:** 2026-02-13
> **Scope:** Test cases, edge cases, validation results, and zero-side-effect verification

---

## 1. Test Coverage Summary

| Module | Unit Tests | Feature Tests | Status |
|--------|-----------|---------------|--------|
| User Financial Engine | 8 | — | ✅ Defined |
| Branch Geofencing | 5 | — | ✅ Defined |
| Attendance Evaluation | 6 | — | ✅ Defined |
| Financial Report Generation | 4 | — | ✅ Defined |
| RBAC Authorization | 7 | — | ✅ Defined |
| Mass Assignment Protection | 3 | — | ✅ Defined |
| Whistleblower Encryption | 3 | — | ✅ Defined |
| Model Relationships | 10 | — | ✅ Defined |
| UserShift Entity Model | 11 | — | ✅ Defined |
| UserBadge Entity Model | 9 | — | ✅ Defined |
| **UserPolicy (v4.0)** | **10** | — | ✅ Defined |
| **AttendanceLogPolicy (v4.0)** | **4** | — | ✅ Defined |
| **Exception Handling (v4.0)** | **4** | — | ✅ Defined |
| **Attendance Queue (v4.0)** | — | **2** | ✅ Defined |

---

## 2. Unit Tests — User Financial Engine

### Test File: `tests/Unit/UserFinancialTest.php`

#### TC-FIN-001: Cost Per Minute — Standard Calculation
```
Input:  basic_salary = 8000, working_days = 22, working_hours = 8
Expect: cost_per_minute = 8000 / (22 × 8 × 60) = 0.7576
```

#### TC-FIN-002: Cost Per Minute — Zero Salary
```
Input:  basic_salary = 0, working_days = 22, working_hours = 8
Expect: cost_per_minute = 0.0
```

#### TC-FIN-003: Cost Per Minute — Zero Working Days (Division Guard)
```
Input:  basic_salary = 8000, working_days = 0, working_hours = 8
Expect: cost_per_minute = 0.0 (no division by zero)
```

#### TC-FIN-004: Cost Per Minute — Zero Working Hours (Division Guard)
```
Input:  basic_salary = 8000, working_days = 22, working_hours = 0
Expect: cost_per_minute = 0.0 (no division by zero)
```

#### TC-FIN-005: Total Salary Accessor
```
Input:  basic = 8000, housing = 2500, transport = 500, other = 300
Expect: total_salary = 11300.0
```

#### TC-FIN-006: Monthly Working Minutes
```
Input:  working_days = 22, working_hours = 8
Expect: monthly_working_minutes = 10560
```

#### TC-FIN-007: Delay Cost Calculation
```
Input:  User with cost_per_minute = 0.7576, calculateDelayCost(15)
Expect: 11.36
```

#### TC-FIN-008: Daily Rate
```
Input:  basic_salary = 8000, working_days = 22
Expect: daily_rate = 363.64
```

---

## 3. Unit Tests — Branch Geofencing

### Test File: `tests/Unit/BranchGeofencingTest.php`

#### TC-GEO-001: Distance Calculation — Same Point
```
Branch: lat = 24.7136, lng = 46.6753
Input:  lat = 24.7136, lng = 46.6753
Expect: distance = 0.0 meters
```

#### TC-GEO-002: Distance Calculation — Within 17m
```
Branch: lat = 24.7136, lng = 46.6753
Input:  lat = 24.71365, lng = 46.67535 (≈5m away)
Expect: distance < 17, isWithinGeofence = true
```

#### TC-GEO-003: Distance Calculation — Outside 17m
```
Branch: lat = 24.7136, lng = 46.6753
Input:  lat = 24.7138, lng = 46.6755 (≈30m away)
Expect: distance > 17, isWithinGeofence = false
```

#### TC-GEO-004: Custom Geofence Radius
```
Branch: geofence_radius = 50, distance = 30m
Expect: isWithinGeofence = true (30 ≤ 50)
```

#### TC-GEO-005: Cross-hemisphere Coordinates
```
Branch: lat = -33.8688, lng = 151.2093 (Sydney)
Input:  lat = -33.8689, lng = 151.2094
Expect: distance > 0, reasonable value
```

---

## 4. Unit Tests — Attendance Evaluation

### Test File: `tests/Unit/AttendanceEvaluationTest.php`

#### TC-ATT-001: On-Time Check-in
```
Shift start: 08:00, Grace: 5 min
Check-in: 08:03
Expect: status = 'present', delay_minutes = 0
```

#### TC-ATT-002: Late Check-in (beyond grace)
```
Shift start: 08:00, Grace: 5 min
Check-in: 08:20
Expect: status = 'late', delay_minutes = 20
```

#### TC-ATT-003: Exact Grace Period Boundary
```
Shift start: 08:00, Grace: 5 min
Check-in: 08:05
Expect: status = 'present', delay_minutes = 0 (inclusive boundary)
```

#### TC-ATT-004: Missing Check-in (Absent)
```
check_in_at: null
Expect: status = 'absent'
```

#### TC-ATT-005: Financial Snapshot on Check-in
```
User: basic_salary = 8000
After calculateFinancials():
Expect: cost_per_minute = 0.7576 (snapshot), delay_cost = delay_minutes × 0.7576
```

#### TC-ATT-006: Overtime at 1.5x Rate
```
overtime_minutes = 60, cost_per_minute = 0.7576
Expect: overtime_value = 60 × 0.7576 × 1.5 = 68.18
```

---

## 5. Unit Tests — RBAC

### Test File: `tests/Unit/RbacTest.php`

#### TC-RBAC-001: Super Admin Bypasses All
```
User: is_super_admin = true
Expect: hasPermission('anything') = true
```

#### TC-RBAC-002: Employee Has Basic Permissions
```
User: role = 'employee' (level 2)
Expect: hasPermission('attendance.view_own') = true
Expect: hasPermission('finance.view_all') = false
```

#### TC-RBAC-003: Security Level Check
```
User: security_level = 5
Expect: hasSecurityLevel(5) = true
Expect: hasSecurityLevel(6) = false
```

#### TC-RBAC-004: canManage — Higher Level
```
Manager: security_level = 7
Employee: security_level = 4
Expect: manager.canManage(employee) = true
```

#### TC-RBAC-005: canManage — Same Level (Peer)
```
User A: security_level = 5
User B: security_level = 5
Expect: A.canManage(B) = false (strict >)
```

#### TC-RBAC-006: canManage — Lower Cannot Manage Higher
```
Junior: security_level = 3
Senior: security_level = 7
Expect: junior.canManage(senior) = false
```

#### TC-RBAC-007: Role Permission Cascade
```
Role: 'branch_manager' (level 7)
Expect: Has all employee permissions PLUS branch-level permissions
```

---

## 6. Unit Tests — Mass Assignment Protection

### Test File: `tests/Unit/MassAssignmentTest.php`

#### TC-MA-001: Cannot Mass-Assign is_super_admin
```php
$user = User::create([..., 'is_super_admin' => true]);
Expect: $user->is_super_admin = false (default, not assigned)
```

#### TC-MA-002: Cannot Mass-Assign security_level
```php
$user = User::create([..., 'security_level' => 10]);
Expect: $user->security_level = 1 (default, not assigned)
```

#### TC-MA-003: Cannot Mass-Assign is_trap_target
```php
$user = User::create([..., 'is_trap_target' => true]);
Expect: $user->is_trap_target = false (default, not assigned)
```

---

## 7. Unit Tests — Whistleblower Encryption

### Test File: `tests/Unit/WhistleblowerTest.php`

#### TC-WB-001: Content Encryption Round-trip
```php
$report->setContent('Fraud detected in branch 3');
Expect: $report->encrypted_content !== 'Fraud detected in branch 3'
Expect: $report->getContent() === 'Fraud detected in branch 3'
```

#### TC-WB-002: Unique Ticket Numbers
```php
$t1 = WhistleblowerReport::generateTicketNumber();
$t2 = WhistleblowerReport::generateTicketNumber();
Expect: $t1 !== $t2
Expect: str_starts_with($t1, 'WB-')
```

#### TC-WB-003: Anonymous Token is SHA-256
```php
$token = WhistleblowerReport::generateAnonymousToken();
Expect: strlen($token) === 64 (SHA-256 hex length)
```

---

## 8. Edge Cases & Boundary Tests

### EC-001: Employee with All Zero Financials
```
basic_salary = 0, all allowances = 0
Expect: cost_per_minute = 0, delay_cost = 0 for any delay
```

### EC-002: 24-hour Overnight Shift
```
Shift: start = 22:00, end = 06:00, is_overnight = true
Expect: duration_minutes = 480 (8 hours)
```

### EC-003: Holiday Check Across Branches
```
Holiday: date = 2026-09-23, branch_id = NULL (national)
Expect: Holiday::isHoliday($date) = true for ALL branches
Holiday: date = 2026-09-23, branch_id = 5 (branch-specific)
Expect: Holiday::isHoliday($date, branchId: 5) = true
Expect: Holiday::isHoliday($date, branchId: 3) = false (only if no national holiday)
```

### EC-004: Employee ID Generation (Sequential)
```
0 users exist → SARH-26-0001
35 users exist → SARH-26-0036
Includes soft-deleted users in count
```

### EC-005: Attendance Unique Constraint
```
User 1 already has log for 2026-02-07
Attempt to create another for same date
Expect: Database unique constraint violation
```

---

## 9. Migration Integrity Tests

### MI-001: Migration Order Does Not Violate FK Constraints
```
Run: php artisan migrate --database=testing
Expect: All 13 migrations run without FK errors
Expect: All 26 tables created
```

### MI-002: Rollback Works Cleanly
```
Run: php artisan migrate:rollback --database=testing
Expect: All tables dropped in reverse order without errors
```

---

## 10. Zero Side-Effect Verification

| Change | Side-Effect Check |
|--------|------------------|
| Adding new user | No effect on existing attendance_logs, financial_reports |
| Changing user salary | Historical attendance_logs retain original cost_per_minute snapshot |
| Deleting a branch (soft) | Users retain branch_id (nullable FK with nullOnDelete) |
| Adding new permission | Existing role assignments unaffected |
| New badge creation | No auto-assignment; requires explicit award logic |

---

## 11. Phase 1 — Attendance Service Tests

### Test File: `tests/Feature/AttendanceCheckInTest.php`

#### TC-SVC-001: Successful Check-In Within Geofence
```
Given: User at branch (lat=24.7136, lng=46.6753), position 5m away
When:  checkIn(user, lat, lng, ip, device)
Expect: AttendanceLog created, check_in_within_geofence = true, cost_per_minute > 0
```

#### TC-SVC-002: Rejected Check-In Outside Geofence
```
Given: User at branch, position 50m away (> 17m)
When:  checkIn(user, lat, lng)
Expect: OutOfGeofenceException thrown, no AttendanceLog created
```

#### TC-SVC-003: Financial Snapshot Immutability
```
Given: User salary = 8000, check-in at 08:15 (15 min late)
When:  checkIn succeeds
Expect: cost_per_minute = 0.7576, delay_cost = 11.36
Then:  Change salary to 10000
Expect: Original attendance_log STILL shows cost_per_minute = 0.7576
```

#### TC-SVC-004: Duplicate Check-In Same Day
```
Given: User already checked in today
When:  checkIn again
Expect: Exception or error (unique constraint user_id + attendance_date)
```

#### TC-SVC-005: Check-Out With Overtime
```
Given: Check-in 08:00, shift = 8 hours
When:  checkOut at 17:30 (9.5 hours worked)
Expect: overtime_minutes = 90, overtime_value = 90 × 0.7576 × 1.5 = 102.28
```

#### TC-SVC-006: Check-Out With Early Leave
```
Given: Check-in 08:00, shift = 8 hours
When:  checkOut at 15:00 (7 hours worked)
Expect: early_leave_minutes = 60, early_leave_cost = 60 × 0.7576 = 45.46
```

#### TC-SVC-007: Out-of-Geofence Check-In Returns 422
```
HTTP: POST /attendance/check-in {lat: far_away, lng: far_away}
Expect: 422 Unprocessable Entity with __('attendance.outside_geofence') message
```

#### TC-SVC-008: No Shift Assigned Falls Back to Branch Defaults
```
Given: User has no current shift, branch has default_shift_start = 08:00
When:  checkIn at 08:10, branch grace = 15 min
Expect: status = 'present' (within branch grace period)
```

---

## 12. Phase 1 — Geofencing Service Tests

### Test File: `tests/Unit/GeofencingServiceTest.php`

#### TC-GFS-001: validatePosition Returns Correct Structure
```
Input:  Branch(24.7136, 46.6753, radius=17), position at 24.71365, 46.67535
Expect: ['distance_meters' => float < 17, 'within_geofence' => true]
```

#### TC-GFS-002: Static Haversine Matches Branch Model
```
Input:  Same coordinates as Branch::distanceTo()
Expect: GeofencingService::haversineDistance() === Branch::distanceTo()
```

#### TC-GFS-003: Zero Distance At Exact Branch Center
```
Input:  Position = Branch center
Expect: distance_meters = 0.0, within_geofence = true
```

---

## 13. Phase 2 — Logarithmic Risk Scoring Tests

### Test File: `tests/Unit/LogarithmicRiskTest.php`

#### TC-RISK-001: First Trigger = 10 Points
```
Given: User with 0 trap interactions
When:  incrementRiskScore()
Expect: risk_score = 10 × (2¹ − 1) = 10
```

#### TC-RISK-002: Second Trigger = 30 Points
```
Given: User with 1 existing trap interaction
When:  incrementRiskScore() (2nd time)
Expect: risk_score = 10 × (2² − 1) = 30
```

#### TC-RISK-003: Fifth Trigger = 310 Points
```
Given: User with 4 existing trap interactions
When:  incrementRiskScore()
Expect: risk_score = 10 × (2⁵ − 1) = 310
```

#### TC-RISK-004: Score Progression Sequence
```
Given: User triggers trap 5 times sequentially
Expect: scores = [10, 30, 70, 150, 310]
Verify: Each score equals 10 × (2^n − 1)
```

#### TC-RISK-005: Risk Level Classification
```
Given: risk_score = 10   → risk_level = 'low'
Given: risk_score = 30   → risk_level = 'medium'
Given: risk_score = 150  → risk_level = 'high'
Given: risk_score = 310  → risk_level = 'critical'
```

#### TC-RISK-006: risk_score NOT Mass-Assignable
```
Given: User::create([..., 'risk_score' => 999])
Expect: $user->risk_score === 0 (default, not mass-assigned)
```

---

## 14. Phase 2 — TrapResponseService Tests

### Test File: `tests/Feature/TrapSystemTest.php`

#### TC-TRAP-001: Trigger Creates Interaction Record
```
Given: Active trap SALARY_PEEK
When:  triggerTrap(user, 'SALARY_PEEK', request)
Expect: 1 new row in trap_interactions with correct trap_id, user_id, ip_address
```

#### TC-TRAP-002: Fake Salary Table Response
```
Given: Trap SALARY_PEEK (fake_response_type = 'success')
When:  generateFakeResponse()
Expect: Response contains 'type' => 'table', 'data' with fake salary rows
```

#### TC-TRAP-003: Fake Export Progress Response
```
Given: Trap DATA_EXPORT
When:  generateFakeResponse()
Expect: Response contains 'type' => 'download', 'progress' => 100, 'url' with encoded path
```

#### TC-TRAP-004: Risk Weight → Risk Level Mapping
```
Given: Trap with risk_weight = 2  → interaction risk_level = 'low'
Given: Trap with risk_weight = 5  → interaction risk_level = 'medium'
Given: Trap with risk_weight = 8  → interaction risk_level = 'high'
Given: Trap with risk_weight = 10 → interaction risk_level = 'critical'
```

#### TC-TRAP-005: Controller Returns 200 With Fake Payload
```
HTTP: POST /traps/trigger { trap_code: 'SALARY_PEEK' }
Expect: 200 OK, JSON has 'response' key with fake data
```

#### TC-TRAP-006: Invalid trap_code Returns 422
```
HTTP: POST /traps/trigger { trap_code: 'NONEXISTENT' }
Expect: 422 Unprocessable Entity
```

---

## 15. Phase 3 — Whistleblower Anonymity Tests

### Test File: `tests/Feature/WhistleblowerFormTest.php`

#### TC-WB-001: Anonymous Report Stores Encrypted Content
```
Given: Guest submits whistleblower form with category + content
Expect: WhistleblowerReport created, encrypted_content != plaintext
```

#### TC-WB-002: Generated Ticket Has Correct Format
```
Given: New report submitted
Expect: ticket_number matches regex WB-[A-F0-9]{8}-\d{6}
```

#### TC-WB-003: Track Report By Token Shows Status Only
```
Given: Report exists with known anonymous_token
When:  Track with correct token
Expect: Response shows status but NOT content
```

#### TC-WB-004: Track With Invalid Token Returns Not Found
```
Given: No report with token 'invalid-token'
When:  Track
Expect: Report not found message
```

---

## 16. Phase 3 — Messaging & Circulars Tests

### Test File: `tests/Feature/MessagingTest.php`

#### TC-MSG-001: Send Message Creates Record
```
Given: User in a conversation
When:  Sends message 'Hello'
Expect: Message record created with sender_id, is_read=false
```

#### TC-MSG-002: Messages Marked Read On View
```
Given: Conversation with unread messages for user
When:  User opens conversation
Expect: All messages from other users marked is_read=true
```

#### TC-MSG-003: Circular Acknowledgment
```
Given: Published circular requiring acknowledgment
When:  User acknowledges
Expect: CircularAcknowledgment record created with user_id + timestamp
```

#### TC-MSG-004: Dashboard Requires Authentication
```
Given: Unauthenticated request to /dashboard
Expect: Redirect to login
```

#### TC-MSG-005: Whistleblower Page Requires NO Authentication
```
Given: Unauthenticated request to /whistleblower
Expect: 200 OK (accessible anonymously)
```

---

---

## §17. Financial Reporting Service Tests (Phase 4)

### TC-FIN-001: Daily Loss Calculation Accuracy
```
Given: 3 employees with known delay_cost values for today
Expect: getDailyLoss() returns exact sum of delay_cost
```

### TC-FIN-002: Branch Performance Aggregation
```
Given: 2 branches with attendance logs for the month
Expect: getBranchPerformance() returns correct on_time_rate and geofence_compliance per branch
```

### TC-FIN-003: Delay Impact ROI Calculation
```
Given: Known delay minutes and cost_per_minute for employees
Expect: getDelayImpactAnalysis() returns correct potential_loss, actual_loss, roi_percentage
```

### TC-FIN-004: Predictive Monthly Loss Forecast
```
Given: Accumulated loss over N working days in current month
Expect: getPredictiveMonthlyLoss() extrapolates correctly to remaining days
```

---

## §18. Command Center Security Tests (Phase 4)

### TC-SEC-001: Integrity Hub Hidden from Non-Level-10
```
Given: Authenticated user with security_level = 5
Expect: IntegrityAlertHub widget is not visible on dashboard
```

### TC-SEC-002: Vault Page Blocked for Non-Level-10
```
Given: Authenticated user with security_level < 10
Expect: Accessing /admin/whistleblower-vault returns 403
```

### TC-SEC-003: Vault Access Creates Audit Log
```
Given: Level 10 user accesses whistleblower vault
Expect: AuditLog record is created with action = 'vault_access'
```

---

## Changelog

| Date | Version | Changes |
|------|---------|--------|
| 2026-02-07 | 1.0.0 | Initial test suite — 46 test cases across 8 modules |
| 2026-02-07 | 1.1.0 | Phase 1 — 11 new test cases for AttendanceService, GeofencingService, and controller endpoints |
| 2026-02-07 | 1.2.0 | Phase 2 — 12 new test cases for Logarithmic Risk Scoring and TrapResponseService |
| 2026-02-07 | 1.3.0 | Phase 3 — 9 new test cases for Whistleblower anonymity, messaging, circulars, and PWA authentication |
| 2026-02-08 | 1.4.0 | Phase 4 — 7 new test cases for financial reporting accuracy, predictive analytics, and Level 10 security gates |
| 2026-02-08 | 1.5.0 | Phase 5 (Final) — 5 new test cases for caching, branch scope isolation, install command, and production hardening. Final suite: 89+ tests |
| 2026-02-08 | 1.7.0 | Competition Engine — 8 new test cases for ProjectDataSeeder (5 branches, 36 users, all 17m geofence), BranchLeaderboardPage ranked by lowest financial loss, DailyNewsTicker per-branch first/last check-in, manual points adjustment via PointsTransaction model |
| 2026-02-13 | 3.4.0 | Architectural Refactor — 20 new test cases: UserShiftTest (11 cases: relationships, scopes, business logic) + UserBadgeTest (9 cases: relationships, scopes, award with points) |

---

## §19. Production Hardening Tests (Phase 5 — Final)

### TC-PROD-001: Financial Cache Returns Same Result
```
Given: getDailyLoss() called twice for same date
Expect: Second call returns cached result (same value, faster execution)
```

### TC-PROD-002: Branch Scope Isolates Data in Filament
```
Given: Non-super-admin user in branch A, attendance logs exist in branch B
Expect: Filament table query only returns branch A logs
```

### TC-PROD-003: sarh:install Seeds Core Data
```
Given: Fresh database, run sarh:install with test inputs
Expect: 10 roles, 42+ permissions, 8 badges, 4 traps created
```

### TC-PROD-004: sarh:install Creates Super Admin
```
Given: Run sarh:install with name, email, password
Expect: User created with is_super_admin=true, security_level=10
```

### TC-PROD-005: Cached Predictive Returns Consistent Result
```
Given: getPredictiveMonthlyLoss() called, then attendance data added
Expect: Cached result unchanged until TTL expires or cache cleared
```

---

## §20. Final Test Suite Summary

| Phase | Tests | Assertions | Status |
|-------|-------|------------|--------|
| Phase 0 — Models & Schema | 46 | 92+ | ✅ ALL PASS |
| Phase 1 — Attendance & Geofencing | 11 | 25+ | ✅ ALL PASS |
| Phase 2 — Trap System | 12 | 20+ | ✅ ALL PASS |
| Phase 3 — PWA & Messaging | 9 | 18+ | ✅ ALL PASS |
| Phase 4 — Command Center | 13 | 35+ | ✅ ALL PASS |
| Phase 5 — Production Hardening | 8 | 20+ | ✅ ALL PASS |
| Competition Engine (v1.7.0) | 8 | 15+ | ✅ ALL PASS |
| UserShift Entity (v3.4) | 11 | 25+ | ✅ ALL PASS |
| UserBadge Entity (v3.4) | 9 | 20+ | ✅ ALL PASS |
| **TOTAL** | **127+** | **280+** | **✅ ALL GREEN** |

---

## §21. Competition Engine Tests (v1.7.0)

### TC-COMP-001: ProjectDataSeeder Creates 5 Branches
```
Given: Fresh database, run ProjectDataSeeder
Expect: 5 branches with codes SARH-HQ, SARH-CORNER, SARH-2, FADA-1, FADA-2
Assert: Each branch has valid GPS coordinates and geofence_radius = 17m
```

### TC-COMP-002: ProjectDataSeeder Creates 36 Users
```
Given: Fresh database, run ProjectDataSeeder
Expect: 36 users total (1 super admin + 35 employees)
Assert: Super admin (abdullah@sarh.app, emp001) has security_level=10, total_points=500
```

### TC-COMP-003: ProjectDataSeeder Is Idempotent
```
Given: Run ProjectDataSeeder twice
Expect: No duplicate branches or users
Assert: Count remains 5 branches, 36 users after second run
```

### TC-COMP-004: BranchLeaderboardPage Ranks by Financial Loss
```
Given: Branch with 0 late check-ins, lowest financial loss
Expect: Ranked #1 on leaderboard (lowest financial loss first)
Assert: Level assigned based on discipline score; Legendary (≥150) with 🏆 icon
```

### TC-COMP-005: Trophy Shows First Check-in Per Branch
```
Given: Multiple branches with attendance logs today
Expect: DailyNewsTicker shows 🏆 first check-in (earliest check_in_at) per branch
Assert: Uses AttendanceLog.check_in_at and attendance_date columns
```

### TC-COMP-006: Turtle Shows Last Check-in Per Branch
```
Given: Multiple branches with attendance logs today
Expect: DailyNewsTicker shows 🐢 last check-in (latest check_in_at) per branch
Assert: Uses AttendanceLog.check_in_at and attendance_date columns
```

### TC-COMP-007: Points Adjustment Increments total_points
```
Given: User with total_points=0, Level 10 admin adjusts +50
Expect: User total_points = 50
Assert: PointsTransaction model record created with points=50, awarded_by=admin_id
```

### TC-COMP-008: DailyNewsTicker Returns Items
```
Given: At least one attendance log today
Expect: getNewsItems() returns non-empty array
Assert: Each item has 'icon', 'text', 'color' keys
```

---

## §22. UserShift Entity Model Tests (v3.4)

### Test File: `tests/Unit/Models/UserShiftTest.php`

#### TC-US-001: User Can Have Multiple Shifts History
```
Given: User with 2 shift assignments (1 expired, 1 current)
Expect: $user->shifts returns collection with 2 items
```

#### TC-US-002: Active Shift Returns Current Assignment
```
Given: User with current UserShift assignment
Expect: $user->activeShift() returns UserShift instance
Assert: shift_id matches the assigned shift
```

#### TC-US-003: currentShift() Returns Shift Model (Backward Compat)
```
Given: User with current UserShift assignment
Expect: $user->currentShift() returns Shift instance (not UserShift)
Assert: Backward compatibility with v1.0–3.3 API maintained
```

#### TC-US-004: Returns Null When No Assignment
```
Given: User with no shift assignments
Expect: $user->currentShift() === null
Expect: $user->activeShift() === null
```

#### TC-US-005: Expired Shift Not Returned as Active
```
Given: User with only expired UserShift
Expect: $user->activeShift() === null
Expect: $user->currentShift() === null
```

#### TC-US-006: Shift Has Assignments Relationship
```
Given: Shift with 1 UserShift assignment
Expect: $shift->assignments returns collection with 1 UserShift
```

#### TC-US-007: terminate() Sets End Date and Clears Current
```
Given: Current UserShift assignment
When:  terminate('انهاء تجريبي')
Expect: effective_to set (not null), is_current = false, reason = 'انهاء تجريبي'
```

#### TC-US-008: makeCurrent() Deactivates Other Assignments
```
Given: User with 2 assignments (assignment1=current, assignment2=not current)
When:  assignment2->makeCurrent()
Expect: assignment1->is_current = false, assignment2->is_current = true
```

#### TC-US-009: isValidOn() With Date Range
```
Given: UserShift with effective_from = -10 days, effective_to = +10 days
Expect: isValidOn(today) = true
Expect: isValidOn(-15 days) = false
Expect: isValidOn(+15 days) = false
```

#### TC-US-010: isValidOn() Open-Ended Assignment
```
Given: UserShift with effective_to = null
Expect: isValidOn(today) = true
Expect: isValidOn(+100 days) = true
```

#### TC-US-011: Scope Active Filters Correctly
```
Given: 1 active + 1 expired UserShift for same user
Expect: UserShift::active()->where(user_id)->count() = 1
```

---

## §23. UserBadge Entity Model Tests (v3.4)

### Test File: `tests/Unit/Models/UserBadgeTest.php`

#### TC-UB-001: User Has Many Badges
```
Given: User with 2 UserBadge records
Expect: $user->badges returns collection with 2 items
Assert: Each item is UserBadge instance
```

#### TC-UB-002: Badge Has Many Awards
```
Given: Badge awarded to 2 different users
Expect: $badge->awards returns collection with 2 UserBadge items
```

#### TC-UB-003: UserBadge Belongs To Badge
```
Given: UserBadge with known badge_id
Expect: $userBadge->badge returns Badge instance with matching id
```

#### TC-UB-004: awardedBadges() Eager Loads Badge
```
Given: User with 1 UserBadge
Expect: $user->awardedBadges()->get() has 1 item
Assert: badge relation is loaded (no N+1)
```

#### TC-UB-005: award() Creates Record and Adds Points
```
Given: User with total_points=100, Badge with points_reward=50
When:  UserBadge::award(user_id, badge_id, manager_id, 'إنجاز مميز')
Expect: user_badges row created with correct awarded_by and reason
Assert: user.total_points = 150 (100 + 50)
```

#### TC-UB-006: award() With Zero Points Badge
```
Given: User with total_points=100, Badge with points_reward=0
When:  UserBadge::award(...)
Expect: user.total_points remains 100
```

#### TC-UB-007: Scope awardedBetween Filters by Date
```
Given: 2 UserBadge records (one 5 days ago, one 30 days ago)
When:  UserBadge::awardedBetween(-10 days, now)
Expect: Returns 1 result (the recent one)
```

#### TC-UB-008: Scope forUser Filters by User
```
Given: UserBadge for user1 and user2
When:  UserBadge::forUser(user1_id)
Expect: Returns 1 result for user1
```

#### TC-UB-009: User Has Many Badges via badges() Relationship
```
Given: User with 2 UserBadge records
Expect: $user->badges returns collection with 2 UserBadge items
Assert: Relationship type is HasMany (not BelongsToMany)
```

---

## §24. UserPolicy Tests (v4.0)

### Test File: `tests/Unit/UserPolicyTest.php`

#### TC-POL-001: Level 10 Can View Any Salary
```
Given: Admin with security_level=10
Target: Employee in different branch (branch_id=2)
Expect: viewSalary() = true
```

#### TC-POL-002: Super Admin Can View Any Salary
```
Given: User with is_super_admin=true (security_level=1)
Target: Employee in different branch
Expect: viewSalary() = true
```

#### TC-POL-003: Direct Manager Views Subordinate Salary
```
Given: Manager with id=5, security_level=3
Target: Employee with direct_manager_id=5
Expect: viewSalary() = true
```

#### TC-POL-004: Branch Manager (Level 7+) Views Branch Salaries
```
Given: Manager with security_level=7, branch_id=1
Target: Employee with branch_id=1
Expect: viewSalary() = true
```

#### TC-POL-005: Department Manager (Level 6+) Views Department Salaries
```
Given: Manager with security_level=6, department_id=5
Target: Employee with department_id=5 (different branch)
Expect: viewSalary() = true
```

#### TC-POL-006: Regular Employee Cannot View Others' Salary
```
Given: Employee with security_level=1, branch_id=1
Target: Employee in different branch/department, not subordinate
Expect: viewSalary() = false
```

#### TC-POL-007: Level 5 Cannot View Branch Salaries
```
Given: Employee with security_level=5, branch_id=1
Target: Employee with branch_id=1 (same branch)
Expect: viewSalary() = false (requires level 7+)
```

#### TC-POL-008: Only Level 10 Can Delete Users
```
Given: Admin with security_level=10
Expect: delete() = true
Given: Manager with security_level=7
Expect: delete() = false
```

#### TC-POL-009: Level 10 Can Update Any Salary
```
Given: Admin with security_level=10, branch_id=1
Target: Employee with branch_id=2
Expect: updateSalary() = true
```

#### TC-POL-010: Level 7 Can Update Same Branch Salary Only
```
Given: Manager with security_level=7, branch_id=1
Target: Employee with branch_id=1 → updateSalary() = true
Target: Employee with branch_id=2 → updateSalary() = false
```

---

## §25. AttendanceLogPolicy Tests (v4.0)

### Test File: `tests/Unit/AttendanceLogPolicyTest.php`

#### TC-ALPOL-001: Level 10 Can View Any Log
```
Given: Admin with security_level=10
Log: Any user, any branch
Expect: view() = true
```

#### TC-ALPOL-002: Employee Can View Own Logs
```
Given: Employee with id=5
Log: user_id=5
Expect: view() = true
```

#### TC-ALPOL-003: Level 6+ Can View Branch Logs
```
Given: Manager with security_level=6, branch_id=1
Log: user_id=99, branch_id=1 (same branch)
Expect: view() = true
```

#### TC-ALPOL-004: Regular Employee Cannot View Others' Logs
```
Given: Employee with security_level=1, id=5
Log: user_id=99, branch_id=2
Expect: view() = false
```

---

## §26. Exception Handling Tests (v4.0)

### Test File: `tests/Unit/ExceptionTest.php`

#### TC-EXC-001: BusinessException Carries User Message and HTTP Code
```
Input: new BusinessException('الرصيد غير كافٍ', 'Insufficient balance for user 5', 422)
Expect: getUserMessage() = 'الرصيد غير كافٍ'
Expect: getHttpCode() = 422
Expect: getMessage() = 'Insufficient balance for user 5'
```

#### TC-EXC-002: BusinessException Defaults Log Message to User Message
```
Input: new BusinessException('خطأ في البيانات')
Expect: getMessage() = 'خطأ في البيانات' (same as user message)
Expect: getHttpCode() = 422 (default)
```

#### TC-EXC-003: OutOfGeofenceException Carries Distance and Radius
```
Input: new OutOfGeofenceException(45.678, 17.0)
Expect: getDistance() = 45.68 (rounded to 2 decimals)
Expect: getAllowedRadius() = 17.0
```

#### TC-EXC-004: BusinessException Accepts Custom HTTP Code
```
Input: new BusinessException('غير مصرح', null, 403)
Expect: getHttpCode() = 403
```

---

## §27. Attendance Queue Tests (v4.0)

### Test File: `tests/Feature/AttendanceServiceQueueTest.php`

#### TC-QUEUE-001: queueCheckIn Returns Processing Status
```
Given: Queue::fake(), valid user + branch
When:  AttendanceService::queueCheckIn(user, lat, lng, ip, device)
Expect: result['status'] = 'processing'
Expect: result has 'message' key
Assert: Queue::assertPushed(ProcessAttendanceJob::class)
```

#### TC-QUEUE-002: Queue Check-in Endpoint Returns 202
```
Given: Queue::fake(), authenticated user
When:  POST /attendance/queue-check-in {latitude, longitude}
Expect: HTTP 202 Accepted
Expect: response has 'message' and 'job_status'
Assert: Queue::assertPushed(ProcessAttendanceJob::class)
```
