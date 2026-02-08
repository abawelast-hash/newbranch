#!/usr/bin/env php
<?php

/**
 * SARH Security Level Upgrade
 * Upgrades all employees to Level 4 (Admin Panel Access)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== SARH Security Level Upgrade ===\n\n";

// Get all active employees (excluding super admins)
$employees = User::where('status', 'active')
    ->where('is_super_admin', false)
    ->get();

echo "Found {$employees->count()} active employees (non-super-admin).\n";
echo "Upgrading all to Security Level 4 (Admin Panel Access)...\n\n";

$upgraded = 0;

foreach ($employees as $emp) {
    // Use forceFill because security_level is guarded
    $emp->forceFill(['security_level' => 4])->save();
    
    echo "✓ [{$emp->employee_id}] {$emp->name_en} → Level 4\n";
    $upgraded++;
}

echo "\n=== Summary ===\n";
echo "Employees Upgraded: {$upgraded}\n";
echo "New Security Level: 4 (Admin Panel Access)\n";
echo "\n=== ALL EMPLOYEES CAN NOW LOGIN ===\n";
echo "Email: [any employee]@sarh.app\n";
echo "Password: 123456\n";
echo "\nSuper Admins (Level 10):\n";

$superAdmins = User::where('is_super_admin', true)->get();
foreach ($superAdmins as $sa) {
    echo "  - {$sa->email} (Level {$sa->security_level})\n";
}

echo "\n=== Done ===\n";
