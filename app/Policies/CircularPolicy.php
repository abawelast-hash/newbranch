<?php

namespace App\Policies;

use App\Models\Circular;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CircularPolicy
{
    use HandlesAuthorization;

    /** المستوى 10 يمر عبر Gate::before — لا نحتاج التحقق هنا */

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Circular $circular): bool
    {
        return $this->userCanSeeCircular($user, $circular);
    }

    public function create(User $user): bool
    {
        return $user->security_level >= 5;
    }

    public function update(User $user, Circular $circular): bool
    {
        // منشئ التعميم أو مستوى 7+
        return $user->id === $circular->created_by
            || $user->security_level >= 7;
    }

    public function delete(User $user, Circular $circular): bool
    {
        return $user->security_level >= 7;
    }

    public function publish(User $user, Circular $circular): bool
    {
        return $user->security_level >= 5;
    }

    // ─── دالة مساعدة مشتركة مع Widget و Job ─────────────────────────────────

    public static function userCanSeeCircular(User $user, Circular $circular): bool
    {
        return match ($circular->target_scope) {
            'all'        => true,
            'branch'     => $user->branch_id === $circular->target_branch_id,
            'department' => $user->department_id === $circular->target_department_id,
            'role'       => $user->role_id === $circular->target_role_id,
            default      => false,
        };
    }
}
