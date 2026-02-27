<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // أيام العطلة الأسبوعية بصيغة JSON مرنة
            // القيمة الافتراضية: جمعة + سبت (النظام السعودي)
            $table->json('weekend_days')
                  ->default('["friday","saturday"]')
                  ->after('grace_period_minutes')
                  ->comment('أيام العطلة الأسبوعية: friday, saturday, sunday, ...');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('weekend_days');
        });
    }
};
