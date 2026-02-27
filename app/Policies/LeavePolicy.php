<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SARH v4.0 — سياسة الصلاحيات لطلبات الإجازة
 *
 * مستويات الوصول:
 *  viewAny  : يرى مدير الفرع (6+) كل طلبات فرعه، والموظف طلباته فقط
 *  view     : الموظف يرى طلبه الشخصي || مدير مباشر || مستوى 6+
 *  create   : أي موظف نشط
 *  update   : الموظف طالما الطلب pending || مستوى 6+ يعدّل الحالة
 *  delete   : الموظف لو طلبه pending || مستوى 8+
 *  approve  : Gate مخصص — مستوى 6+ أو المدير المباشر
 */
class LeavePolicy
{
    use HandlesAuthorization;

    /**
     * God Mode shortcircuit.
     */
    public function before(User $user): ?bool
    {
        if ($user->security_level >= 10 || $user->is_super_admin) {
            return true;
        }

        return null;
    }

    /**
     * هل يستطيع المستخدم رؤية قائمة الإجازات؟
     * المستوى 6+ يرى كل إجازات فرعه، الأدنى يرى طلباته الشخصية فقط.
     */
    public function viewAny(User $user): bool
    {
        return true; // الجميع يصل — الفلترة تتم في الاستعلام
    }

    /**
     * هل يستطيع المستخدم عرض طلب إجازة بعينه؟
     */
    public function view(User $user, Leave $leave): bool
    {
        // الموظف يرى طلبه
        if ($user->id === $leave->user_id) {
            return true;
        }

        // المدير المباشر يرى إجازات مرؤوسيه
        $requester = $leave->user;
        if ($requester && $requester->direct_manager_id === $user->id) {
            return true;
        }

        // مستوى 6+ يرى إجازات فرعه
        return $user->security_level >= 6 && $user->branch_id === $leave->branch_id;
    }

    /**
     * هل يستطيع المستخدم تقديم طلب إجازة لنفسه؟
     */
    public function create(User $user): bool
    {
        return true; // كل موظف نشط يحق له التقدم بطلب
    }

    /**
     * هل يستطيع المستخدم تعديل طلب إجازة؟
     * الموظف يعدّل طلبه ما دام pending.
     * المستوى 6+ يعدّل حالة الطلب (approve/reject).
     */
    public function update(User $user, Leave $leave): bool
    {
        // الموظف يعدّل طلبه إن لم يُبتّ فيه بعد
        if ($user->id === $leave->user_id && $leave->status === 'pending') {
            return true;
        }

        // مدير مباشر يعدّل
        $requester = $leave->user;
        if ($requester && $requester->direct_manager_id === $user->id) {
            return true;
        }

        // مستوى 6+ يعدّل إجازات فرعه
        return $user->security_level >= 6 && $user->branch_id === $leave->branch_id;
    }

    /**
     * هل يستطيع المستخدم حذف طلب إجازة؟
     */
    public function delete(User $user, Leave $leave): bool
    {
        // الموظف يحذف طلبه pending فقط
        if ($user->id === $leave->user_id && $leave->status === 'pending') {
            return true;
        }

        return $user->security_level >= 8;
    }

    /**
     * هل يستطيع المستخدم الموافقة/رفض طلب إجازة؟
     * مستوى 6+ أو المدير المباشر.
     */
    public function approve(User $user, Leave $leave): bool
    {
        // لا يوافق الموظف على إجازة نفسه
        if ($user->id === $leave->user_id) {
            return false;
        }

        $requester = $leave->user;
        if ($requester && $requester->direct_manager_id === $user->id) {
            return true;
        }

        return $user->security_level >= 6 && $user->branch_id === $leave->branch_id;
    }
}
