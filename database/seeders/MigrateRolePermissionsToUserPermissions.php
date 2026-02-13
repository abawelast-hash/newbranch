<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

/**
 * SARH v4.1 — ترحيل صلاحيات الأدوار إلى صلاحيات فردية
 *
 * يأخذ كل مستخدم له دور → ويحوّل صلاحيات دوره إلى سجلات UserPermission (grant).
 * يتخطى السجلات الموجودة مسبقًا لتجنب التكرار.
 *
 * الأمر: php artisan db:seed --class=MigrateRolePermissionsToUserPermissions
 */
class MigrateRolePermissionsToUserPermissions extends Seeder
{
    public function run(): void
    {
        $users = User::with('role.permissions')->whereNotNull('role_id')->get();
        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if (!$user->role || $user->role->permissions->isEmpty()) {
                continue;
            }

            foreach ($user->role->permissions as $permission) {
                // تحقق إذا كان هناك سجل موجود لهذا المستخدم+الصلاحية
                $exists = UserPermission::where('user_id', $user->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                UserPermission::create([
                    'user_id'       => $user->id,
                    'permission_id' => $permission->id,
                    'type'          => 'grant',
                    'granted_by'    => 1, // System (User #1)
                    'reason'        => 'ترحيل تلقائي من الدور: ' . ($user->role->name_ar ?? $user->role->slug),
                ]);

                $created++;
            }
        }

        $this->command->info("✅ تم ترحيل صلاحيات الأدوار — أُنشئت {$created} صلاحية جديدة، تُخطيت {$skipped} موجودة.");
    }
}
