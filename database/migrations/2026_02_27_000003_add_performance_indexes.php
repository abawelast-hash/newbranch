<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── attendance_logs — أكثر جدول استعلاماً ──────────────────────────
        Schema::table('attendance_logs', function (Blueprint $table) {
            // فهرس مركّب للبحث بالموظف + التاريخ (الأكثر استخداماً)
            if (!$this->indexExists('attendance_logs', 'idx_al_user_date')) {
                $table->index(['user_id', 'date'],    'idx_al_user_date');
            }
            // فهرس الفرع + التاريخ (للتقارير والـ widgets)
            if (!$this->indexExists('attendance_logs', 'idx_al_branch_date')) {
                $table->index(['branch_id', 'date'],  'idx_al_branch_date');
            }
            // فهرس الحالة (للفلاتر)
            if (!$this->indexExists('attendance_logs', 'idx_al_status')) {
                $table->index(['status'],             'idx_al_status');
            }
            // فهرس تكلفة الغياب/التأخير
            if (!$this->indexExists('attendance_logs', 'idx_al_cost')) {
                $table->index(['cost'],               'idx_al_cost');
            }
        });

        // ─── analytics_snapshots ─────────────────────────────────────────────
        Schema::table('analytics_snapshots', function (Blueprint $table) {
            if (!$this->indexExists('analytics_snapshots', 'idx_as_branch_date')) {
                $table->index(['branch_id', 'snapshot_date'], 'idx_as_branch_date');
            }
            if (!$this->indexExists('analytics_snapshots', 'idx_as_user_date')) {
                $table->index(['user_id', 'snapshot_date'],   'idx_as_user_date');
            }
            if (!$this->indexExists('analytics_snapshots', 'idx_as_period')) {
                $table->index(['snapshot_date', 'period_type'], 'idx_as_period');
            }
        });

        // ─── circulars ───────────────────────────────────────────────────────
        Schema::table('circulars', function (Blueprint $table) {
            if (!$this->indexExists('circulars', 'idx_cir_scope_branch')) {
                $table->index(['target_scope', 'target_branch_id'], 'idx_cir_scope_branch');
            }
            if (!$this->indexExists('circulars', 'idx_cir_published')) {
                $table->index(['published_at'],                     'idx_cir_published');
            }
            if (!$this->indexExists('circulars', 'idx_cir_expires')) {
                $table->index(['expires_at'],                       'idx_cir_expires');
            }
        });

        // ─── users ───────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_usr_branch_active')) {
                $table->index(['branch_id', 'is_active'],       'idx_usr_branch_active');
            }
            if (!$this->indexExists('users', 'idx_usr_security_level')) {
                $table->index(['security_level'],               'idx_usr_security_level');
            }
            if (!$this->indexExists('users', 'idx_usr_department')) {
                $table->index(['department_id'],                'idx_usr_department');
            }
        });

        // ─── performance_alerts ──────────────────────────────────────────────
        if (Schema::hasTable('performance_alerts')) {
            Schema::table('performance_alerts', function (Blueprint $table) {
                if (!$this->indexExists('performance_alerts', 'idx_pa_user_type')) {
                    $table->index(['user_id', 'alert_type'], 'idx_pa_user_type');
                }
                if (!$this->indexExists('performance_alerts', 'idx_pa_created')) {
                    $table->index(['created_at'],            'idx_pa_created');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_al_user_date');
            $table->dropIndexIfExists('idx_al_branch_date');
            $table->dropIndexIfExists('idx_al_status');
            $table->dropIndexIfExists('idx_al_cost');
        });

        Schema::table('analytics_snapshots', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_as_branch_date');
            $table->dropIndexIfExists('idx_as_user_date');
            $table->dropIndexIfExists('idx_as_period');
        });

        Schema::table('circulars', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_cir_scope_branch');
            $table->dropIndexIfExists('idx_cir_published');
            $table->dropIndexIfExists('idx_cir_expires');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_usr_branch_active');
            $table->dropIndexIfExists('idx_usr_security_level');
            $table->dropIndexIfExists('idx_usr_department');
        });
    }

    /** تحقق آمن من وجود index قبل الإنشاء */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return !empty($indexes);
    }
};
