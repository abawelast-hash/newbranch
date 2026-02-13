<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SARH v2.0 — Dynamic Settings Engine
 *
 * Central settings table for app_name, welcome messages, branding assets.
 * Single-row design: only one settings record (id=1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Brand Identity
            $table->string('app_name')->default('صرح الإتقان');
            $table->string('app_name_en')->default('SARH Al-Itqan');

            // Welcome Screen
            $table->string('welcome_title')->default('مرحباً بكم في صرح الإتقان');
            $table->text('welcome_body')->nullable();

            // Branding Assets
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // PWA Metadata
            $table->string('pwa_name')->default('صرح الإتقان');
            $table->string('pwa_short_name')->default('صرح');
            $table->string('pwa_theme_color')->default('#FF8C00');
            $table->string('pwa_background_color')->default('#ffffff');

            $table->timestamps();
        });

        // Seed the default settings row
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'app_name'       => 'صرح الإتقان',
            'app_name_en'    => 'SARH Al-Itqan',
            'welcome_title'  => 'مرحباً بكم في صرح الإتقان',
            'welcome_body'   => 'نظام إدارة الموارد البشرية المتكامل',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
