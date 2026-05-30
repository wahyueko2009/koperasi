<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RetailController;
use App\Http\Controllers\Api\SavingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Members
    Route::apiResource('members', MemberController::class)
        ->only(['index', 'show', 'store', 'update'])
        ->names([
            'index' => 'api.members.index',
            'show' => 'api.members.show',
            'store' => 'api.members.store',
            'update' => 'api.members.update',
        ]);
    Route::get('members/{member}/balance', [MemberController::class, 'balance']);

    // Savings
    Route::post('savings', [SavingController::class, 'store']);
    Route::get('members/{memberId}/savings', [SavingController::class, 'memberSavings']);

    // Loans
    Route::apiResource('loans', LoanController::class)
        ->only(['store'])
        ->names([
            'store' => 'api.loans.store',
        ]);
    Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
    Route::post('loans/{loan}/disburse', [LoanController::class, 'disburse']);
    Route::post('loans/{loan}/payment', [LoanController::class, 'payment']);
    Route::get('loans/{loan}/payments', [LoanController::class, 'paymentHistory']);

    // Retail
    Route::get('retail/items', [RetailController::class, 'items']);
    Route::post('retail/transactions', [RetailController::class, 'store']);
    Route::get('retail/transactions', [RetailController::class, 'transactions']);

    // Accounting
    Route::get('accounting/coa', [AccountingController::class, 'chartOfAccounts']);
    Route::get('accounting/general-ledger', [AccountingController::class, 'generalLedger']);
    Route::get('accounting/journal-entries', [AccountingController::class, 'journalEntries']);
    Route::get('accounting/income-statement', [AccountingController::class, 'incomeStatement']);
    Route::get('accounting/balance-sheet', [AccountingController::class, 'balanceSheet']);
});
