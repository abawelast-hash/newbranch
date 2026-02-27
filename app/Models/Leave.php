<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'leave_type',     // annual | sick | emergency | unpaid | maternity | other
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',         // pending | approved | rejected | cancelled
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'approved_at' => 'datetime',
            'days_count'  => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOnDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('start_date', '<=', $date)
                     ->whereDate('end_date',   '>=', $date);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getTypeNameAttribute(): string
    {
        return match ($this->leave_type) {
            'annual'    => 'إجازة سنوية',
            'sick'      => 'إجازة مرضية',
            'emergency' => 'إجازة طارئة',
            'unpaid'    => 'إجازة بدون راتب',
            'maternity' => 'إجازة أمومة',
            default     => 'إجازة أخرى',
        };
    }
}
