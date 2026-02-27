<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SARH v4.0 — سياسة الصلاحيات للفروع
 *
 * مستويات الوصول:
 *  viewAny  : 4+  (مدراء ومحاسبون وما فوق)
 *  view     : 4+ أو موظف في نفس الفرع
 *  create   : 8+  (مدير عام أو أعلى)
 *  update   : 7+ لأي فرع || 5+ لفرعه الخاص
 *  delete   : 9+  (صلاحية عليا)
 *  restore  : 9+
 */
class BranchPolicy
{
    use HandlesAuthorization;

    /**
     * God Mode shortcircuit — المستوى 10 يتجاوز جميع القيود.
     */
    public function before(User $user): ?bool
    {
        if ($user->security_level >= 10 || $user->is_super_admin) {
            return true;
        }

        return null; // defer to individual methods
    }

    /**
     * هل يستطيع المستخدم رؤية قائمة الفروع؟
     */
    public function viewAny(User $user): bool
    {
        return $user->security_level >= 4;
    }

    /**
     * هل يستطيع المستخدم رؤية تفاصيل فرع معين؟
     * يسمح إذا: مستوى 4+ أو الموظف ينتمي لهذا الفرع.
     */
    public function view(User $user, Branch $branch): bool
    {
        return $user->security_level >= 4
            || $user->branch_id === $branch->id;
    }

    /**
     * هل يستطيع المستخدم إنشاء فرع جديد؟
     */
    public function create(User $user): bool
    {
        return $user->security_level >= 8;
    }

    /**
     * هل يستطيع المستخدم تعديل بيانات فرع؟
     *  - المستوى 7+ يعدّل أي فرع
     *  - المستوى 5+ يعدّل فرعه الخاص فقط
     */
    public function update(User $user, Branch $branch): bool
    {
        if ($user->security_level >= 7) {
            return true;
        }

        return $user->security_level >= 5 && $user->branch_id === $branch->id;
    }

    /**
     * هل يستطيع المستخدم حذف فرع؟
     * يُعدّ عملية حساسة جداً — يتطلب مستوى 9+.
     */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->security_level >= 9;
    }

    /**
     * هل يستطيع المستخدم استعادة فرع محذوف (soft delete)؟
     */
    public function restore(User $user, Branch $branch): bool
    {
        return $user->security_level >= 9;
    }

    /**
     * هل يستطيع المستخدم الحذف النهائي (force delete)؟
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return $user->security_level >= 10;
    }
}
