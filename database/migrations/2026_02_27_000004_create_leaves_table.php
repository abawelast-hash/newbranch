<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 30)->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('days_count')->default(1);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // فهارس للاستعلامات الشائعة
            $table->index(['user_id', 'status'],             'idx_leaves_user_status');
            $table->index(['user_id', 'start_date', 'end_date'], 'idx_leaves_user_dates');
            $table->index(['branch_id', 'start_date'],       'idx_leaves_branch_date');
            $table->index(['status'],                        'idx_leaves_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
