<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Circular extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_en',
        'body_ar',
        'body_en',
        'priority',
        'target_scope',
        'target_branch_id',
        'target_department_id',
        'target_role_id',
        'created_by',
        'requires_acknowledgment',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgment' => 'boolean',
            'published_at'            => 'datetime',
            'expires_at'              => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function acknowledgments()
    {
        return $this->hasMany(CircularAcknowledgment::class);
    }

    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function getBodyAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->body_ar : $this->body_en;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeActive($query)
    {
        return $query->published()
                     ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * فلتر التعاميم المرئية للمستخدم حسب target_scope.
     * المستوى 10 يرى الكل عبر Gate::before — لا حاجة لاستثناء هنا.
     */
    public function scopeForUser($query, ?\App\Models\User $user = null)
    {
        $user ??= auth()->user();

        if (!$user) {
            return $query->whereRaw('0 = 1'); // لا مستخدم = لا نتائج
        }

        // المستوى 10 يرى الكل
        if ($user->security_level >= 10 || $user->is_super_admin) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('target_scope', 'all')
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_scope', 'branch')
                     ->where('target_branch_id', $user->branch_id);
              })
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_scope', 'department')
                     ->where('target_department_id', $user->department_id);
              })
              ->orWhere(function ($q2) use ($user) {
                  $q2->where('target_scope', 'role')
                     ->where('target_role_id', $user->role_id);
              });
        });
    }
}
