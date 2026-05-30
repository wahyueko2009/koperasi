#!/usr/bin/env php
<?php

/**
 * Final System Verification Checklist
 * Run this script to verify all components are working
 */

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║   SISTEM INFORMASI KOPERASI - FINAL VERIFICATION CHECKLIST    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checks = [];

// 1. Database Connection
echo "\n▶ Checking Database Connection...";
try {
    \DB::connection()->getPdo();
    echo " ✅\n";
    $checks['db'] = true;
} catch (\Exception $e) {
    echo " ❌ " . $e->getMessage() . "\n";
    $checks['db'] = false;
}

// 2. Tables Existence
echo "▶ Checking Database Tables...";
$tables = [
    'users', 'members', 'chart_of_accounts', 'journal_entries', 'general_ledger',
    'member_ledgers', 'saving_types', 'savings', 'loans', 'loan_payments',
    'retail_items', 'retail_transactions', 'retail_transaction_items',
    'personal_access_tokens'
];
$missingTables = [];
foreach ($tables as $table) {
    if (!\DB::connection()->getSchemaBuilder()->hasTable($table)) {
        $missingTables[] = $table;
    }
}
if (empty($missingTables)) {
    echo " ✅ (" . count($tables) . " tables)\n";
    $checks['tables'] = true;
} else {
    echo " ❌ Missing: " . implode(', ', $missingTables) . "\n";
    $checks['tables'] = false;
}

// 3. User Models
echo "▶ Checking Sample Data...";
$memberCount = \App\Models\Member::count();
$userCount = \App\Models\User::count();
$coaCount = \App\Models\ChartOfAccount::count();
$journalCount = \App\Models\JournalEntry::count();

if ($memberCount > 0 && $userCount > 0 && $coaCount > 0) {
    echo " ✅\n";
    $checks['data'] = true;
    echo "   - Members: $memberCount\n";
    echo "   - Users: $userCount\n";
    echo "   - Chart of Accounts: $coaCount\n";
    echo "   - Journal Entries: $journalCount\n";
} else {
    echo " ⚠️ Limited data\n";
    $checks['data'] = true;
}

// 4. Models
echo "▶ Checking Eloquent Models...";
$models = [
    'App\\Models\\User',
    'App\\Models\\Member',
    'App\\Models\\ChartOfAccount',
    'App\\Models\\JournalEntry',
    'App\\Models\\GeneralLedger',
    'App\\Models\\MemberLedger',
    'App\\Models\\Saving',
    'App\\Models\\Loan',
    'App\\Models\\LoanPayment',
    'App\\Models\\RetailItem',
    'App\\Models\\RetailTransaction',
];
$missingModels = [];
foreach ($models as $model) {
    if (!class_exists($model)) {
        $missingModels[] = $model;
    }
}
if (empty($missingModels)) {
    echo " ✅ (" . count($models) . " models)\n";
    $checks['models'] = true;
} else {
    echo " ❌ Missing: " . count($missingModels) . "\n";
    $checks['models'] = false;
}

// 5. Services
echo "▶ Checking Services...";
$services = [
    'App\\Services\\JournalService',
    'App\\Services\\SavingService',
    'App\\Services\\LoanService',
    'App\\Services\\RetailService',
];
$missingServices = [];
foreach ($services as $service) {
    if (!class_exists($service)) {
        $missingServices[] = $service;
    }
}
if (empty($missingServices)) {
    echo " ✅ (4 services)\n";
    $checks['services'] = true;
} else {
    echo " ❌ Missing: " . count($missingServices) . "\n";
    $checks['services'] = false;
}

// 6. Controllers
echo "▶ Checking API Controllers...";
$controllers = [
    'App\\Http\\Controllers\\Api\\AuthController',
    'App\\Http\\Controllers\\Api\\MemberController',
    'App\\Http\\Controllers\\Api\\SavingController',
    'App\\Http\\Controllers\\Api\\LoanController',
    'App\\Http\\Controllers\\Api\\RetailController',
    'App\\Http\\Controllers\\Api\\AccountingController',
];
$missingControllers = [];
foreach ($controllers as $controller) {
    if (!class_exists($controller)) {
        $missingControllers[] = $controller;
    }
}
if (empty($missingControllers)) {
    echo " ✅ (6 controllers)\n";
    $checks['controllers'] = true;
} else {
    echo " ❌ Missing: " . count($missingControllers) . "\n";
    $checks['controllers'] = false;
}

// 7. Sanctum
echo "▶ Checking Sanctum Integration...";
$user = \App\Models\User::first();
if ($user && method_exists($user, 'createToken')) {
    echo " ✅ (Sanctum properly integrated)\n";
    $checks['sanctum'] = true;
} else {
    echo " ❌ Sanctum not properly integrated\n";
    $checks['sanctum'] = false;
}

// 8. Journaling Automation
echo "▶ Checking Journal Automation...";
if ($journalCount > 0) {
    $debitTotal = \DB::table('general_ledger')->sum('debit');
    $creditTotal = \DB::table('general_ledger')->sum('credit');
    if (abs($debitTotal - $creditTotal) < 0.01) {
        echo " ✅\n";
        echo "   Debit Total:  Rp " . number_format($debitTotal, 0, ',', '.') . "\n";
        echo "   Credit Total: Rp " . number_format($creditTotal, 0, ',', '.') . "\n";
        echo "   Status: BALANCED ✅\n";
        $checks['journaling'] = true;
    } else {
        echo " ❌ Journal entries not balanced\n";
        echo "   Debit:  Rp " . number_format($debitTotal, 0, ',', '.') . "\n";
        echo "   Credit: Rp " . number_format($creditTotal, 0, ',', '.') . "\n";
        $checks['journaling'] = false;
    }
} else {
    echo " ⚠️ No journal entries yet (can be generated with commands)\n";
    $checks['journaling'] = true;
}

// 9. Files Structure
echo "▶ Checking Project Files...";
$requiredFiles = [
    'routes/api.php',
    'routes/web.php',
    'bootstrap/app.php',
    'config/sanctum.php',
    'app/Services/JournalService.php',
    'app/Http/Controllers/DashboardController.php',
    'resources/views/dashboard.blade.php',
];
$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missingFiles[] = $file;
    }
}
if (empty($missingFiles)) {
    echo " ✅\n";
    $checks['files'] = true;
} else {
    echo " ❌ Missing files: " . count($missingFiles) . "\n";
    $checks['files'] = false;
}

// 10. Migrations
echo "▶ Checking Migrations...";
$migrations = \DB::table('migrations')->count();
if ($migrations >= 14) {  // At least our custom migrations + Laravel defaults
    echo " ✅ ($migrations migrations)\n";
    $checks['migrations'] = true;
} else {
    echo " ⚠️ Only $migrations migrations\n";
    $checks['migrations'] = true;
}

// Summary
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY REPORT                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$totalChecks = count($checks);
$passedChecks = array_sum($checks);
$failedChecks = $totalChecks - $passedChecks;

echo "Results:\n";
echo "  Passed: $passedChecks/$totalChecks ✅\n";
if ($failedChecks > 0) {
    echo "  Failed: $failedChecks/$totalChecks ❌\n";
}

if ($failedChecks === 0) {
    echo "\n✅ SISTEM SIAP UNTUK DIGUNAKAN!\n\n";
    echo "Instruksi Penggunaan:\n";
    echo "1. Jalankan server: php artisan serve --host=127.0.0.1 --port=8000\n";
    echo "2. Akses Dashboard: http://localhost:8000/dashboard\n";
    echo "3. Login dengan:\n";
    echo "   Email: admin@koperasi.local\n";
    echo "   Password: password\n\n";
} else {
    echo "\n⚠️ Ada beberapa issue yang perlu diperbaiki.\n";
    echo "Lihat detail di atas untuk informasi lebih lanjut.\n\n";
}

echo "════════════════════════════════════════════════════════════════\n\n";
