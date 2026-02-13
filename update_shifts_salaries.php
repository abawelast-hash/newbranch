<?php
/**
 * Update salaries, allowances, and shift times
 * - All allowances → 0
 * - All basic salaries → 3000
 * - صرح branches (SARH-*): 08:00 to 21:00
 * - فضا branches (FADA-*): 08:00 to 20:00
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Branch;

// ── 1. Update all salaries & zero allowances ─────────────
$count = User::where('security_level', '<', 10)->update([
    'basic_salary'       => 3000,
    'housing_allowance'  => 0,
    'transport_allowance'=> 0,
    'other_allowances'   => 0,
]);
echo "✅ Updated {$count} employees: salary=3000, all allowances=0\n";

// ── 2. SARH branches: 08:00 – 21:00 ─────────────────────
$sarh = Branch::where('code', 'like', 'SARH%')->update([
    'default_shift_start' => '08:00',
    'default_shift_end'   => '21:00',
]);
echo "✅ SARH branches ({$sarh}): shift 08:00–21:00\n";

// ── 3. FADA branches: 08:00 – 20:00 ─────────────────────
$fada = Branch::where('code', 'like', 'FADA%')->update([
    'default_shift_start' => '08:00',
    'default_shift_end'   => '20:00',
]);
echo "✅ FADA branches ({$fada}): shift 08:00–20:00\n";

// ── Verify ───────────────────────────────────────────────
echo "\n── Branches ──\n";
foreach (Branch::all() as $b) {
    echo "  {$b->name_ar}: {$b->default_shift_start} – {$b->default_shift_end}\n";
}

echo "\n── Sample employees ──\n";
User::where('security_level', '<', 10)->limit(3)->get()->each(function ($u) {
    echo "  {$u->name_ar}: salary={$u->basic_salary}, housing={$u->housing_allowance}, transport={$u->transport_allowance}, other={$u->other_allowances}\n";
});
