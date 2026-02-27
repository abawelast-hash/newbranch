<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circular_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('delivered_at')->useCurrent();

            // منع التكرار
            $table->unique(['circular_id', 'user_id'], 'uq_delivery');
            $table->index(['user_id', 'delivered_at'], 'idx_delivery_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circular_deliveries');
    }
};
