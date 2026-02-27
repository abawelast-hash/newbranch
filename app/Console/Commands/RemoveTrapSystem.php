<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveTrapSystem extends Command
{
    protected $signature   = 'remove:trap-system {--force : تجاوز التأكيد}';
    protected $description = 'إزالة نظام Trap بالكامل من المشروع';

    /** الملفات التي سيتم حذفها */
    private array $filesToDelete = [
        'app/Models/Trap.php',
        'app/Models/TrapInteraction.php',
        'app/Events/TrapTriggered.php',
        'app/Listeners/LogTrapInteraction.php',
        'app/Services/TrapResponseService.php',
        'app/Http/Controllers/TrapController.php',
        'app/Filament/Resources/TrapResource.php',
        'app/Filament/Resources/TrapInteractionResource.php',
        'app/Filament/Pages/TrapAuditPage.php',
        'app/Filament/Widgets/RiskWidget.php',
        'app/Filament/Widgets/IntegrityAlertHub.php',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=red;options=bold>⚠  إزالة نظام Trap من SARH</>');
        $this->line('  ─────────────────────────────────');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('هل أنت متأكد من حذف نظام Trap بالكامل؟ (لا يمكن التراجع)')) {
                $this->info('  تم الإلغاء.');
                return self::SUCCESS;
            }
        }

        $report   = [];
        $deleted  = 0;
        $skipped  = 0;

        // ─── 1. حذف المسارات من web.php ───────────────────────────────────
        $this->line('  <fg=cyan>[1/4]</> تنظيف routes/web.php...');
        $this->cleanRoutes($report);

        // ─── 2. تشغيل migrations الحذف ────────────────────────────────────
        $this->line('  <fg=cyan>[2/4]</> تشغيل migrations...');
        $this->runDropMigrations($report);

        // ─── 3. حذف الملفات ───────────────────────────────────────────────
        $this->line('  <fg=cyan>[3/4]</> حذف الملفات...');
        foreach ($this->filesToDelete as $relativePath) {
            $fullPath = base_path($relativePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
                $this->line("         <fg=green>✓</> $relativePath");
                $report[] = "DELETED: $relativePath";
                $deleted++;
            } else {
                $this->line("         <fg=yellow>–</> $relativePath (غير موجود)");
                $report[] = "SKIPPED (not found): $relativePath";
                $skipped++;
            }
        }

        // ─── 4. تنظيف الوثائق ─────────────────────────────────────────────
        $this->line('  <fg=cyan>[4/4]</> تنظيف الوثائق...');
        $this->cleanDocs($report);

        // ─── تنظيف config cache ────────────────────────────────────────────
        Artisan::call('optimize:clear');
        $this->line('         <fg=green>✓</> optimize:clear');

        // ─── التقرير النهائي ───────────────────────────────────────────────
        $this->newLine();
        $this->line('  ─────────────────────────────────');
        $this->line("  <fg=green>✓ محذوف:</> $deleted ملف");
        $this->line("  <fg=yellow>– متجاوز:</> $skipped ملف");
        $this->newLine();

        // كتابة تقرير نصي
        $reportPath = storage_path('logs/trap_removal_' . date('Y_m_d_His') . '.txt');
        file_put_contents($reportPath, implode(PHP_EOL, $report));
        $this->line("  <fg=cyan>التقرير:</> $reportPath");
        $this->newLine();

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function cleanRoutes(array &$report): void
    {
        $webPhpPath = base_path('routes/web.php');
        $content    = file_get_contents($webPhpPath);

        // حذف كتلة Trap System Routes بالكامل (مع التعليق)
        $pattern = '/\/\*\n\s*\|---.*?\| Trap System Routes.*?\*\/\nRoute::middleware.*?}\);\n/s';
        $cleaned = preg_replace($pattern, '', $content);

        // حذف use TrapController إن وُجد
        $cleaned = preg_replace('/^use App\\\\Http\\\\Controllers\\\\TrapController;\n/m', '', $cleaned);

        if ($cleaned !== $content) {
            file_put_contents($webPhpPath, $cleaned);
            $this->line('         <fg=green>✓</> routes/web.php — حُذفت كتلة Trap Routes');
            $report[] = 'CLEANED:  routes/web.php (Trap routes block removed)';
        } else {
            $this->line('         <fg=yellow>–</> routes/web.php (لم توجد مسارات Trap)');
            $report[] = 'SKIPPED:  routes/web.php (no trap routes found)';
        }
    }

    private function runDropMigrations(array &$report): void
    {
        $tables = ['trap_interactions', 'traps'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->line("         <fg=green>✓</> DROP TABLE $table");
                $report[] = "DB DROP:  $table";
            } else {
                $this->line("         <fg=yellow>–</> $table (غير موجود في DB)");
                $report[] = "DB SKIP:  $table (not found)";
            }
        }
    }

    private function cleanDocs(array &$report): void
    {
        $docFiles = glob(base_path('docs/*.md')) ?: [];

        // كلمات مرتبطة بـ Trap يجب إخفاؤها من الوثائق
        $trapPhrases = [
            '/\|.*?Trap.*?\|.*\n/',                  // صفوف جداول Trap
            '/^#{1,4} .*Trap.*\n([\s\S]*?)(?=^#{1,4} |\z)/m', // أقسام Trap
            '/^- .*[Tt]rap.*\n/m',                   // عناصر قائمة Trap
            '/`TrapController`[^\n]*/m',
            '/`TrapResource`[^\n]*/m',
            '/`IntegrityAlertHub`[^\n]*/m',
            '/`RiskWidget`[^\n]*/m',
        ];

        foreach ($docFiles as $docFile) {
            $original = file_get_contents($docFile);
            $cleaned  = $original;

            foreach ($trapPhrases as $pattern) {
                $cleaned = preg_replace($pattern, '', $cleaned);
            }

            // إزالة أسطر فارغة متعددة
            $cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);

            if ($cleaned !== $original) {
                file_put_contents($docFile, $cleaned);
                $name = basename($docFile);
                $this->line("         <fg=green>✓</> docs/$name (نُظّف)");
                $report[] = "DOC CLEAN: docs/$name";
            }
        }
    }
}
