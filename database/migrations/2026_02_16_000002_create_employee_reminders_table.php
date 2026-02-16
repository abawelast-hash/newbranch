<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reminder_key'); // عقد عمل، تجديد إقامة، إلخ
            $table->date('reminder_date');
            $table->text('notes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'reminder_date']);
            $table->index(['reminder_date', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reminders');
    }
};
