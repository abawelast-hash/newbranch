<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // trap_interactions أولاً (foreign key على traps)
        Schema::dropIfExists('trap_interactions');
        Schema::dropIfExists('traps');
    }

    public function down(): void
    {
        // لا إعادة — الحذف نهائي بقصد
        // أعد تشغيل migration الإنشاء يدوياً إن احتجت التراجع
    }
};
