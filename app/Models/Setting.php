<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * SARH v2.0 — Dynamic Settings Model
 *
 * Single-row pattern: Settings::instance() always returns the one settings record.
 * Caches for 1 hour. Clears cache on save.
 *
 * @property string $app_name
 * @property string $app_name_en
 * @property string $welcome_title
 * @property string|null $welcome_body
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string $pwa_name
 * @property string $pwa_short_name
 * @property string $pwa_theme_color
 * @property string $pwa_background_color
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'app_name',
        'app_name_en',
        'welcome_title',
        'welcome_body',
        'logo_path',
        'favicon_path',
        'pwa_name',
        'pwa_short_name',
        'pwa_theme_color',
        'pwa_background_color',
    ];

    /**
     * Get the singleton settings instance (cached for 1 hour).
     */
    public static function instance(): static
    {
        return Cache::remember('sarh_settings', 3600, function () {
            return static::firstOrCreate(['id' => 1], [
                'app_name'      => 'صرح الإتقان',
                'app_name_en'   => 'SARH Al-Itqan',
                'welcome_title' => 'مرحباً بكم في صرح الإتقان',
            ]);
        });
    }

    /**
     * Clear cache on save so changes take effect immediately.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('sarh_settings');
        });
    }

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Get the full URL for the favicon.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon_path) {
            return null;
        }

        return Storage::disk('public')->url($this->favicon_path);
    }
}
