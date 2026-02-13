<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-RBAC-001: Super Admin Bypasses All Permission Checks
     */
    public function test_super_admin_bypasses_all(): void
    {
        $user = new User();
        $user->forceFill(['is_super_admin' => true]);

        $this->assertTrue($user->hasPermission('nonexistent.permission'));
        $this->assertTrue($user->hasPermission('finance.view_all'));
    }

    /**
     * TC-RBAC-002: Individual Grant — User Has Permission via UserPermission
     */
    public function test_individual_grant_gives_permission(): void
    {
        $perm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $user = User::factory()->create();

        // Grant via UserPermission
        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $perm->id,
            'type'          => 'grant',
            'reason'        => 'test',
            'granted_by'    => $user->id,
        ]);

        $this->assertTrue($user->hasPermission('attendance.view_own'));
        $this->assertFalse($user->hasPermission('finance.view_all'));
    }

    /**
     * TC-RBAC-003: Role Assignment Does NOT Grant Permissions (honorary)
     */
    public function test_role_does_not_grant_permissions(): void
    {
        $role = Role::create([
            'name_ar' => 'موظف',
            'name_en' => 'Employee',
            'slug'    => 'employee_test',
            'level'   => 2,
        ]);

        $perm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $role->permissions()->attach($perm);

        $user = User::factory()->create(['role_id' => $role->id]);

        // Role has the permission, but user does NOT — roles are honorary
        $this->assertFalse($user->hasPermission('attendance.view_own'));
    }

    /**
     * TC-RBAC-004: Revoke Overrides Grant
     */
    public function test_revoke_overrides_grant(): void
    {
        $perm = Permission::create([
            'name_ar' => 'عرض المالية',
            'name_en' => 'View Finance',
            'slug'    => 'finance.view_all',
            'group'   => 'finance',
        ]);

        $user = User::factory()->create();

        // First grant, then revoke (revoke replaces grant due to unique constraint)
        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $perm->id,
            'type'          => 'revoke',
            'reason'        => 'access removed',
            'granted_by'    => $user->id,
        ]);

        $this->assertFalse($user->hasPermission('finance.view_all'));
    }

    /**
     * TC-RBAC-005: Implied Permissions via Dependency Map
     */
    public function test_implied_permissions(): void
    {
        // Create both permissions
        Permission::create([
            'name_ar' => 'إدارة الحضور',
            'name_en' => 'Manage Attendance',
            'slug'    => 'attendance.manage',
            'group'   => 'attendance',
        ]);

        Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $managePerm = Permission::where('slug', 'attendance.manage')->first();

        $user = User::factory()->create();

        // Grant only attendance.manage
        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $managePerm->id,
            'type'          => 'grant',
            'reason'        => 'test implied',
            'granted_by'    => $user->id,
        ]);

        // attendance.manage implies attendance.view_own
        $this->assertTrue($user->hasPermission('attendance.manage'));
        $this->assertTrue($user->hasPermission('attendance.view_own'));
    }

    /**
     * TC-RBAC-006: Expired Permission Is Ignored
     */
    public function test_expired_permission_ignored(): void
    {
        $perm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $user = User::factory()->create();

        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $perm->id,
            'type'          => 'grant',
            'reason'        => 'temporary',
            'granted_by'    => $user->id,
            'expires_at'    => now()->subDay(), // expired yesterday
        ]);

        $this->assertFalse($user->hasPermission('attendance.view_own'));
    }

    /**
     * TC-RBAC-007: Security Level Check
     */
    public function test_security_level_check(): void
    {
        $user = new User();
        $user->forceFill(['security_level' => 5]);

        $this->assertTrue($user->hasSecurityLevel(5));
        $this->assertTrue($user->hasSecurityLevel(3));
        $this->assertFalse($user->hasSecurityLevel(6));
    }

    /**
     * TC-RBAC-008: canManage — Higher Level Manages Lower
     */
    public function test_can_manage_lower_level(): void
    {
        $manager = new User();
        $manager->forceFill(['security_level' => 7, 'is_super_admin' => false]);

        $employee = new User();
        $employee->forceFill(['security_level' => 4]);

        $this->assertTrue($manager->canManage($employee));
    }

    /**
     * TC-RBAC-009: canManage — Same Level Cannot Manage Peer
     */
    public function test_cannot_manage_same_level(): void
    {
        $userA = new User();
        $userA->forceFill(['security_level' => 5, 'is_super_admin' => false]);

        $userB = new User();
        $userB->forceFill(['security_level' => 5]);

        $this->assertFalse($userA->canManage($userB));
    }

    /**
     * TC-RBAC-010: canManage — Lower Cannot Manage Higher
     */
    public function test_lower_cannot_manage_higher(): void
    {
        $junior = new User();
        $junior->forceFill(['security_level' => 3, 'is_super_admin' => false]);

        $senior = new User();
        $senior->forceFill(['security_level' => 7]);

        $this->assertFalse($junior->canManage($senior));
    }

    /**
     * TC-RBAC-011: Super Admin canManage Everyone
     */
    public function test_super_admin_can_manage_anyone(): void
    {
        $admin = new User();
        $admin->forceFill(['is_super_admin' => true, 'security_level' => 10]);

        $target = new User();
        $target->forceFill(['security_level' => 10]);

        $this->assertTrue($admin->canManage($target));
    }

    /**
     * TC-RBAC-012: hasAnyPermission Helper
     */
    public function test_has_any_permission(): void
    {
        $perm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $user = User::factory()->create();

        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $perm->id,
            'type'          => 'grant',
            'reason'        => 'test',
            'granted_by'    => $user->id,
        ]);

        $this->assertTrue($user->hasAnyPermission(['attendance.view_own', 'finance.view_all']));
        $this->assertFalse($user->hasAnyPermission(['finance.view_all', 'users.create']));
    }

    /**
     * TC-RBAC-013: getEffectivePermissions Returns Only Individual Grants + Implied
     */
    public function test_effective_permissions_no_role_fallback(): void
    {
        $role = Role::create([
            'name_ar' => 'مدير',
            'name_en' => 'Manager',
            'slug'    => 'manager_test',
            'level'   => 6,
        ]);

        $rolePerm = Permission::create([
            'name_ar' => 'عرض المالية',
            'name_en' => 'View Finance',
            'slug'    => 'finance.view_all',
            'group'   => 'finance',
        ]);

        $userPerm = Permission::create([
            'name_ar' => 'عرض الحضور',
            'name_en' => 'View Own Attendance',
            'slug'    => 'attendance.view_own',
            'group'   => 'attendance',
        ]);

        $role->permissions()->attach($rolePerm);

        $user = User::factory()->create(['role_id' => $role->id]);

        UserPermission::create([
            'user_id'       => $user->id,
            'permission_id' => $userPerm->id,
            'type'          => 'grant',
            'reason'        => 'test',
            'granted_by'    => $user->id,
        ]);

        $effective = $user->getEffectivePermissions();

        // Should have the individually granted permission
        $this->assertTrue($effective->contains('attendance.view_own'));
        // Should NOT have the role permission (roles are honorary)
        $this->assertFalse($effective->contains('finance.view_all'));
    }
}
