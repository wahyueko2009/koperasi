<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    private function authorizeAccountingAccess(): void
    {
        abort_unless(
            auth()->user()?->isAdministrator() || auth()->user()?->isAdmin(),
            403,
            'Akses data akuntansi hanya untuk administrator atau admin.'
        );
    }

    /**
     * Lihat Chart of Accounts
     */
    public function chartOfAccounts()
    {
        $this->authorizeAccountingAccess();

        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code')
            ->paginate();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Lihat General Ledger per akun
     */
    public function generalLedger(Request $request)
    {
        $this->authorizeAccountingAccess();

        $validated = $request->validate([
            'account_code' => 'required|exists:chart_of_accounts,code',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $account = ChartOfAccount::where('code', $validated['account_code'])->firstOrFail();

        $query = GeneralLedger::where('account_id', $account->id);

        if ($request->has('from_date')) {
            $query->whereDate('entry_date', '>=', $validated['from_date']);
        }

        if ($request->has('to_date')) {
            $query->whereDate('entry_date', '<=', $validated['to_date']);
        }

        $ledger = $query->with('journalEntry')
            ->orderBy('entry_date')
            ->paginate();

        // Calculate balance
        $debitSum = $query->sum('debit');
        $creditSum = $query->sum('credit');
        $balance = $account->normal_balance === 'debit' ? $debitSum - $creditSum : $creditSum - $debitSum;

        return response()->json([
            'success' => true,
            'account' => $account,
            'balance' => $balance,
            'data' => $ledger,
        ]);
    }

    /**
     * Lihat Journal Entries
     */
    public function journalEntries(Request $request)
    {
        $this->authorizeAccountingAccess();

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'reference_type' => 'nullable|string',
            'status' => 'nullable|in:posted,reversed,cancelled',
        ]);

        $query = JournalEntry::query();

        if ($request->has('from_date')) {
            $query->whereDate('entry_date', '>=', $validated['from_date']);
        }

        if ($request->has('to_date')) {
            $query->whereDate('entry_date', '<=', $validated['to_date']);
        }

        if ($request->has('reference_type')) {
            $query->where('reference_type', $validated['reference_type']);
        }

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        $entries = $query->with('debitAccount', 'creditAccount')
            ->latest('entry_date')
            ->paginate();

        return response()->json([
            'success' => true,
            'data' => $entries,
        ]);
    }

    /**
     * Laporan Laba Rugi
     */
    public function incomeStatement(Request $request)
    {
        $this->authorizeAccountingAccess();

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
        ]);

        // Income accounts
        $incomeAccounts = ChartOfAccount::where('account_type', 'income')->get();
        $incomeData = [];
        $totalIncome = 0;

        foreach ($incomeAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod(
                $account->id,
                $validated['from_date'],
                $validated['to_date']
            );
            $incomeData[] = [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalIncome += $balance;
        }

        // Expense accounts
        $expenseAccounts = ChartOfAccount::where('account_type', 'expense')->get();
        $expenseData = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountBalanceForPeriod(
                $account->id,
                $validated['from_date'],
                $validated['to_date']
            );
            $expenseData[] = [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalExpense += $balance;
        }

        $netIncome = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'data' => [
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'income' => [
                    'details' => $incomeData,
                    'total' => $totalIncome,
                ],
                'expenses' => [
                    'details' => $expenseData,
                    'total' => $totalExpense,
                ],
                'net_income' => $netIncome,
            ],
        ]);
    }

    /**
     * Laporan Neraca (Balance Sheet)
     */
    public function balanceSheet(Request $request)
    {
        $this->authorizeAccountingAccess();

        $asOfDate = $request->get('as_of_date', now()->format('Y-m-d'));

        // Assets
        $assetAccounts = ChartOfAccount::where('account_type', 'asset')->get();
        $assetData = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $account) {
            $balance = $this->getAccountBalanceAsOfDate($account->id, $asOfDate);
            $assetData[] = [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalAssets += $balance;
        }

        // Liabilities
        $liabilityAccounts = ChartOfAccount::where('account_type', 'liability')->get();
        $liabilityData = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = $this->getAccountBalanceAsOfDate($account->id, $asOfDate);
            $liabilityData[] = [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalLiabilities += $balance;
        }

        // Equity
        $equityAccounts = ChartOfAccount::where('account_type', 'equity')->get();
        $equityData = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $account) {
            $balance = $this->getAccountBalanceAsOfDate($account->id, $asOfDate);
            $equityData[] = [
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $totalEquity += $balance;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'as_of_date' => $asOfDate,
                'assets' => [
                    'details' => $assetData,
                    'total' => $totalAssets,
                ],
                'liabilities' => [
                    'details' => $liabilityData,
                    'total' => $totalLiabilities,
                ],
                'equity' => [
                    'details' => $equityData,
                    'total' => $totalEquity,
                ],
            ],
        ]);
    }

    private function getAccountBalanceForPeriod(int $accountId, $fromDate, $toDate): float
    {
        $account = ChartOfAccount::find($accountId);

        $debitSum = DB::table('general_ledger')
            ->where('account_id', $accountId)
            ->whereDate('entry_date', '>=', $fromDate)
            ->whereDate('entry_date', '<=', $toDate)
            ->sum('debit');

        $creditSum = DB::table('general_ledger')
            ->where('account_id', $accountId)
            ->whereDate('entry_date', '>=', $fromDate)
            ->whereDate('entry_date', '<=', $toDate)
            ->sum('credit');

        if ($account->normal_balance === 'debit') {
            return $debitSum - $creditSum;
        }

        return $creditSum - $debitSum;
    }

    private function getAccountBalanceAsOfDate(int $accountId, $asOfDate): float
    {
        $account = ChartOfAccount::find($accountId);

        $debitSum = DB::table('general_ledger')
            ->where('account_id', $accountId)
            ->whereDate('entry_date', '<=', $asOfDate)
            ->sum('debit');

        $creditSum = DB::table('general_ledger')
            ->where('account_id', $accountId)
            ->whereDate('entry_date', '<=', $asOfDate)
            ->sum('credit');

        if ($account->normal_balance === 'debit') {
            return $debitSum - $creditSum;
        }

        return $creditSum - $debitSum;
    }
}
