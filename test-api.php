#!/usr/bin/env php
<?php

/**
 * API Testing Script
 * Test semua endpoints untuk memastikan API berfungsi
 */

$baseUrl = 'http://localhost:8000/api';
$adminEmail = 'admin@koperasi.local';
$adminPassword = 'password';
$token = null;

function sendRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;

    $url = $baseUrl . $endpoint;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            $token ? "Authorization: Bearer {$token}" : '',
        ],
    ]);

    if ($data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [
        'code' => $httpCode,
        'data' => json_decode($response, true),
    ];
}

function testEndpoint($name, $method, $endpoint, $data = null, $token = null, $checkSuccess = true) {
    echo "\n🔷 Testing: $name";
    echo "\n   {$method} {$endpoint}";

    $result = sendRequest($method, $endpoint, $data, $token);

    if ($result['code'] >= 200 && $result['code'] < 300) {
        if ($checkSuccess && !($result['data']['success'] ?? true)) {
            echo "\n   ❌ FAILED: " . ($result['data']['message'] ?? 'Unknown error');
            return null;
        }
        echo "\n   ✅ SUCCESS ({$result['code']})";
        return $result['data'];
    } else {
        echo "\n   ❌ FAILED ({$result['code']}): " . ($result['data']['message'] ?? 'Unknown error');
        return null;
    }
}

echo "╔════════════════════════════════════════════════════════════╗";
echo "\n║        API ENDPOINTS TEST - Koperasi Digital Mandiri       ║";
echo "\n╚════════════════════════════════════════════════════════════╝";

// 1. LOGIN
echo "\n\n=== AUTHENTICATION TESTS ===";
$loginResult = testEndpoint(
    'Login Admin',
    'POST',
    '/login',
    ['email' => $adminEmail, 'password' => $adminPassword],
    null,
    false
);

if ($loginResult && $loginResult['success']) {
    $token = $loginResult['data']['token'];
    echo "\n   Token: " . substr($token, 0, 20) . "...";
} else {
    echo "\n❌ Login failed! Stopping tests.";
    exit(1);
}

// 2. GET CURRENT USER
testEndpoint('Get Current User', 'GET', '/me', null, $token);

// 3. MEMBERS
echo "\n\n=== MEMBERS TESTS ===";
testEndpoint('List Members', 'GET', '/members', null, $token);
$member = testEndpoint('Get Member Detail', 'GET', '/members/1', null, $token);
testEndpoint('Get Member Balance', 'GET', '/members/1/balance', null, $token);

// 4. ACCOUNTING
echo "\n\n=== ACCOUNTING TESTS ===";
testEndpoint('Chart of Accounts', 'GET', '/accounting/coa', null, $token);
testEndpoint('General Ledger (Kas)', 'GET', '/accounting/general-ledger?account_code=1001', null, $token);
testEndpoint('Journal Entries', 'GET', '/accounting/journal-entries', null, $token);
testEndpoint('Income Statement', 'GET', '/accounting/income-statement?from_date=2026-01-01&to_date=2026-12-31', null, $token);
testEndpoint('Balance Sheet', 'GET', '/accounting/balance-sheet?as_of_date=2026-04-08', null, $token);

// 5. SAVINGS
echo "\n\n=== SAVINGS TESTS ===";
testEndpoint('Get Member Savings', 'GET', '/members/1/savings', null, $token);

// 6. LOANS
echo "\n\n=== LOANS TESTS ===";
testEndpoint('Get Loans (Paginated)', 'GET', '/loans', null, $token);
testEndpoint('Get Loan Payment History', 'GET', '/loans/1/payments', null, $token);

// 7. RETAIL
echo "\n\n=== RETAIL TESTS ===";
testEndpoint('List Retail Items', 'GET', '/retail/items?category=indomaret', null, $token);
testEndpoint('Get Retail Transactions', 'GET', '/retail/transactions?member_id=1', null, $token);

// 8. LOGOUT
echo "\n\n=== LOGOUT TEST ===";
testEndpoint('Logout', 'POST', '/logout', null, $token, false);

echo "\n\n╔════════════════════════════════════════════════════════════╗";
echo "\n║                    TEST COMPLETED! ✅                       ║";
echo "\n╚════════════════════════════════════════════════════════════╝\n";
