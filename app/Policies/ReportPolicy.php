<?php

namespace App\Policies;

use App\Models\ReportFormula;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SARH v4.0 — سياسة الصلاحيات لصيغ التقارير
 *
 * صيغ التقارير حساسة لأنها تُشغَّل ضمن محرك الحسابات.
 * يُقيَّد إنشاؤها وتعديلها لمستويات عليا فقط.
 *
 * مستويات الوصول:
 *  viewAny  : 5+  (مشرف وما فوق)
 *  view     : 5+  أو صاحب الصيغة
 *  create   : 9+  (مطور / مالك النظام)
 *  update   : 9+  أو صاحب الصيغة بمستوى 7+
 *  delete   : 10  (God Mode فقط)
 */
class ReportPolicy
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
     * هل يستطيع المستخدم رؤية قائمة صيغ التقارير؟
     */
    public function viewAny(User $user): bool
    {
        return $user->security_level >= 5;
    }

    /**
     * هل يستطيع المستخدم عرض صيغة بعينها؟
     */
    public function view(User $user, ReportFormula $formula): bool
    {
        return $user->security_level >= 5
            || $user->id === $formula->created_by;
    }

    /**
     * هل يستطيع المستخدم إنشاء صيغة جديدة؟
     * مقيّد بمستوى 9+ لأن الصيغ تُنفَّذ داخل المحرك الحسابي.
     */
    public function create(User $user): bool
    {
        return $user->security_level >= 9;
    }

    /**
     * هل يستطيع المستخدم تعديل صيغة محددة؟
     */
    public function update(User $user, ReportFormula $formula): bool
    {
        if ($user->security_level >= 9) {
            return true;
        }

        // صاحب الصيغة بمستوى 7+ يستطيع التعديل
        return $user->security_level >= 7 && $user->id === $formula->created_by;
    }

    /**
     * هل يستطيع المستخدم حذف صيغة؟
     * حساس جداً — يتطلب صلاحية كاملة (handled by Gate::before).
     */
    public function delete(User $user, ReportFormula $formula): bool
    {
        return $user->security_level >= 10;
    }
}
