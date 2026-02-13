<?php

namespace Database\Seeders;

use App\Models\UserShift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SARH v3.4 — مصحح بيانات user_shifts
 *
 * يملأ الأعمدة الجديدة (assigned_by, approved_by, approved_at, reason)
 * في السجلات الموجودة مسبقاً قبل ترقية الموديل.
 *
 * ⚠️ يُشغّل مرة واحدة فقط بعد تشغيل المايجريشن.
 * ⚠️ لا يمسح أي بيانات — فقط يضيف قيم للأعمدة الجديدة الفارغة.
 */
class FixUserShiftsDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminId = DB::table('users')
            ->where('is_super_admin', true)
            ->value('id') ?? 1;

        // 1. ملء أعمدة التدقيق في user_shifts
        $updated = DB::table('user_shifts')
            ->whereNull('assigned_by')
            ->update([
                'assigned_by' => $superAdminId,
                'approved_by' => $superAdminId,
                'approved_at' => now(),
                'reason'      => 'ترحيل تلقائي من النظام القديم (v3.4)',
            ]);

        $this->command->info("✅ user_shifts: تم تحديث {$updated} سجل بأعمدة التدقيق.");

        // 2. ملء عمود awarded_by في user_badges
        $badgesUpdated = DB::table('user_badges')
            ->whereNull('awarded_by')
            ->update([
                'awarded_by' => $superAdminId,
            ]);

        $this->command->info("✅ user_badges: تم تحديث {$badgesUpdated} سجل بعمود awarded_by.");
    }
}
