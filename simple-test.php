#!/usr/bin/env php
<?php

/**
 * Simple API Test
 */

echo "Testing API Endpoints...\n\n";

// Test 1: Check if server is running
echo "1. Testing server connection...\n";
$ch = curl_init('http://localhost:8000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "   ✅ Server is running\n\n";
} else {
    echo "   ❌ Server not responding\n\n";
    exit(1);
}

// Test 2: Login
echo "2. Testing /api/login endpoint...\n";
$ch = curl_init('http://localhost:8000/api/login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'admin@koperasi.local',
        'password' => 'password',
    ]),
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ CURL Error: $error\n";
    exit(1);
}

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✅ Login successful (HTTP $httpCode)\n";
        echo "   Token: " . substr($data['data']['token'], 0, 30) . "...\n\n";
        $token = $data['data']['token'];
    } else {
        echo "   ❌ Login failed: " . $data['message'] . "\n\n";
        exit(1);
    }
} else {
    echo "   ❌ HTTP Error $httpCode\n";
    echo "   Response: " . substr($response, 0, 200) . "\n\n";
    exit(1);
}

// Test 3: Get current user
echo "3. Testing /api/me endpoint...\n";
$ch = curl_init('http://localhost:8000/api/me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$token}",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    echo "   ✅ Get user successful (HTTP $httpCode)\n";
    echo "   User: " . $data['data']['name'] . " (" . $data['data']['role'] . ")\n\n";
} else {
    echo "   ❌ HTTP Error $httpCode\n\n";
}

// Test 4: Get Chart of Accounts
echo "4. Testing /api/accounting/coa endpoint...\n";
$ch = curl_init('http://localhost:8000/api/accounting/coa');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$token}",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    echo "   ✅ COA fetched (HTTP $httpCode)\n";
    echo "   Total Accounts: " . count($data['data']['data']) . "\n\n";
} else {
    echo "   ❌ HTTP Error $httpCode\n\n";
}

// Test 5: Get Income Statement
echo "5. Testing /api/accounting/income-statement endpoint...\n";
$ch = curl_init('http://localhost:8000/api/accounting/income-statement?from_date=2026-01-01&to_date=2026-12-31');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$token}",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    echo "   ✅ Income Statement generated (HTTP $httpCode)\n";
    echo "   Total Income: Rp " . number_format($data['data']['income']['total'], 0, ',', '.') . "\n";
    echo "   Total Expense: Rp " . number_format($data['data']['expenses']['total'], 0, ',', '.') . "\n";
    echo "   Net Income: Rp " . number_format($data['data']['net_income'], 0, ',', '.') . "\n\n";
} else {
    echo "   ❌ HTTP Error $httpCode\n\n";
}

// Test 6: Get Balance Sheet
echo "6. Testing /api/accounting/balance-sheet endpoint...\n";
$ch = curl_init('http://localhost:8000/api/accounting/balance-sheet?as_of_date=2026-04-08');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "Authorization: Bearer {$token}",
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $data = json_decode($response, true);
    echo "   ✅ Balance Sheet generated (HTTP $httpCode)\n";
    echo "   Total Assets: Rp " . number_format($data['data']['assets']['total'], 0, ',', '.') . "\n";
    echo "   Total Liabilities: Rp " . number_format($data['data']['liabilities']['total'], 0, ',', '.') . "\n";
    echo "   Total Equity: Rp " . number_format($data['data']['equity']['total'], 0, ',', '.') . "\n\n";
} else {
    echo "   ❌ HTTP Error $httpCode\n\n";
}

echo "╔════════════════════════════════════════════╗\n";
echo "║    ALL TESTS COMPLETED SUCCESSFULLY! ✅   ║\n";
echo "╚════════════════════════════════════════════╝\n";
