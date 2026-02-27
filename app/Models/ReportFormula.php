<?php

namespace App\Models;

use App\Services\SafeFormulaEngine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SARH v1.9.0 — صيغ التقارير الديناميكية
 *
 * يسمح لمالك النظام بتعريف صيغ حسابية مخصصة للتقارير.
 * مثال: (attendance * 0.4) + (task_completion * 0.6)
 */
class ReportFormula extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'formula',
        'variables',
        'description_ar',
        'description_en',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMULA ENGINE
    |--------------------------------------------------------------------------
    */

    /**
     * Evaluate the formula with given variable values.
     *
     * Uses SafeFormulaEngine (Shunting-yard / RPN) — NO eval() is called.
     *
     * @param  array<string, float>  $values  e.g. ['attendance' => 95.5, 'task_completion' => 87.0]
     * @return float|null  Returns null if variables are missing or the formula is invalid.
     */
    public function evaluate(array $values): ?float
    {
        // Guard: all declared variables must be provided
        $requiredVars = array_keys($this->variables ?? []);
        foreach ($requiredVars as $var) {
            if (!array_key_exists($var, $values)) {
                return null;
            }
        }

        try {
            $result = app(SafeFormulaEngine::class)->evaluate($this->formula, $values);
            return round($result, 4);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get available variables with their descriptions.
     */
    public function getVariablesList(): array
    {
        $vars = $this->variables ?? [];
        $list = [];

        foreach ($vars as $key => $description) {
            $list[] = [
                'key'         => $key,
                'description' => is_array($description)
                    ? ($description[app()->getLocale()] ?? $description['ar'] ?? $key)
                    : $description,
            ];
        }

        return $list;
    }

    /**
     * Validate formula syntax without executing.
     * Uses SafeFormulaEngine::validate() — NO eval().
     */
    public function validateFormula(): bool
    {
        return app(SafeFormulaEngine::class)
            ->validate($this->formula, $this->variables ?? []);
    }
}
