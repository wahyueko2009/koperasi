<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use App\Models\GlAccountMapping;
use App\Models\GlPeriod;
use App\Models\JournalEntry;
use App\Models\MemberLedger;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JournalService
{
    public function createJournal(
        string $referenceType,
        int $referenceId,
        string $debitAccountCode,
        string $creditAccountCode,
        float $amount,
        string $memo = null,
        ?DateTimeInterface $entryDate = null
    ): JournalEntry {
        return $this->createJournalEntry(
            referenceType: $referenceType,
            referenceId: $referenceId,
            sourceModule: $referenceType,
            debitAccountCode: $debitAccountCode,
            creditAccountCode: $creditAccountCode,
            amount: $amount,
            memo: $memo,
            entryDate: $entryDate,
        );
    }

    public function createMappedJournal(
        string $sourceModule,
        string $transactionType,
        string $referenceType,
        int $referenceId,
        float $amount,
        string $memo = null,
        ?DateTimeInterface $entryDate = null
    ): JournalEntry {
        $mapping = $this->resolveMapping($sourceModule, $transactionType);

        return $this->createJournalEntry(
            referenceType: $referenceType,
            referenceId: $referenceId,
            sourceModule: $sourceModule,
            debitAccountCode: (string) $mapping->debitAccount?->code,
            creditAccountCode: (string) $mapping->creditAccount?->code,
            amount: $amount,
            memo: $memo,
            entryDate: $entryDate,
        );
    }

    public function createManualJournal(
        array $attributes,
        bool $postImmediately = false
    ): JournalEntry {
        $entryDate = Carbon::parse((string) $attributes['entry_date']);
        $period = $this->resolvePeriod(
            entryDate: $entryDate,
            periodId: $attributes['period_id'] ?? null,
            allowDraft: ! $postImmediately,
        );

        $journal = JournalEntry::create([
            'entry_date' => $entryDate->toDateString(),
            'period_id' => $period->id,
            'cost_center_id' => $attributes['cost_center_id'] ?? null,
            'reference_type' => 'manual',
            'reference_id' => 0,
            'source_module' => 'manual',
            'reference_number' => $attributes['reference_number'] ?? $this->generateReferenceNumber('MAN'),
            'debit_account_id' => $attributes['debit_account_id'],
            'credit_account_id' => $attributes['credit_account_id'],
            'amount' => $attributes['amount'],
            'memo' => $attributes['memo'] ?? null,
            'status' => $postImmediately ? 'posted' : 'draft',
            'posted_at' => $postImmediately ? now() : null,
            'created_by' => Auth::id(),
            'posted_by' => $postImmediately ? Auth::id() : null,
        ]);

        if ($postImmediately) {
            $this->postToGeneralLedger($journal);
        }

        return $journal;
    }

    public function postExistingJournal(JournalEntry $journal): JournalEntry
    {
        if ($journal->status === 'posted') {
            return $journal;
        }

        $period = $this->resolvePeriod(
            entryDate: Carbon::parse($journal->entry_date),
            periodId: $journal->period_id,
            allowDraft: false,
        );

        $journal->update([
            'period_id' => $period->id,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => Auth::id(),
        ]);

        $this->postToGeneralLedger($journal->fresh());

        return $journal->fresh();
    }

    public function postToGeneralLedger(JournalEntry $journal): void
    {
        $exists = GeneralLedger::query()->where('journal_entry_id', $journal->id)->exists();
        if ($exists) {
            return;
        }

        GeneralLedger::create([
            'account_id' => $journal->debit_account_id,
            'entry_date' => $journal->entry_date,
            'journal_entry_id' => $journal->id,
            'debit' => $journal->amount,
            'credit' => 0,
            'memo' => $journal->memo,
        ]);

        GeneralLedger::create([
            'account_id' => $journal->credit_account_id,
            'entry_date' => $journal->entry_date,
            'journal_entry_id' => $journal->id,
            'debit' => 0,
            'credit' => $journal->amount,
            'memo' => $journal->memo,
        ]);
    }

    public function updateMemberLedger(
        int $memberId,
        string $ledgerScope,
        string $transactionType,
        int $transactionId,
        float $debit = 0,
        float $credit = 0,
        string $memo = null,
        ?DateTimeInterface $entryDate = null
    ): MemberLedger {
        $entryDate = $entryDate ? Carbon::instance(Carbon::parse($entryDate)) : now();

        $lastBalance = MemberLedger::where('member_id', $memberId)
            ->where('ledger_scope', $ledgerScope)
            ->latest('entry_date')
            ->first()?->balance ?? 0;

        $balance = $lastBalance + $debit - $credit;

        return MemberLedger::create([
            'member_id' => $memberId,
            'ledger_scope' => $ledgerScope,
            'entry_date' => $entryDate,
            'transaction_type' => $transactionType,
            'transaction_id' => $transactionId,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
            'memo' => $memo,
        ]);
    }

    public function getAccountBalance(string $accountCode): float
    {
        $account = ChartOfAccount::where('code', $accountCode)->firstOrFail();

        $debitSum = DB::table('general_ledger')
            ->where('account_id', $account->id)
            ->sum('debit');

        $creditSum = DB::table('general_ledger')
            ->where('account_id', $account->id)
            ->sum('credit');

        if ($account->normal_balance === 'debit') {
            return $debitSum - $creditSum;
        }

        return $creditSum - $debitSum;
    }

    public function getMemberBalance(int $memberId, string $ledgerScope = 'general'): float
    {
        return MemberLedger::where('member_id', $memberId)
            ->where('ledger_scope', $ledgerScope)
            ->latest('entry_date')
            ->first()?->balance ?? 0;
    }

    private function createJournalEntry(
        string $referenceType,
        int $referenceId,
        string $sourceModule,
        string $debitAccountCode,
        string $creditAccountCode,
        float $amount,
        string $memo = null,
        ?DateTimeInterface $entryDate = null
    ): JournalEntry {
        $debitAccount = ChartOfAccount::where('code', $debitAccountCode)->firstOrFail();
        $creditAccount = ChartOfAccount::where('code', $creditAccountCode)->firstOrFail();
        $entryDate = $entryDate ? Carbon::parse($entryDate) : now();
        $period = $this->resolvePeriod($entryDate);

        return DB::transaction(function () use (
            $referenceType,
            $referenceId,
            $sourceModule,
            $debitAccount,
            $creditAccount,
            $amount,
            $memo,
            $entryDate,
            $period
        ) {
            $journal = JournalEntry::create([
                'entry_date' => $entryDate->toDateString(),
                'period_id' => $period->id,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'source_module' => $sourceModule,
                'reference_number' => $this->generateReferenceNumber(strtoupper(substr($sourceModule, 0, 3))),
                'debit_account_id' => $debitAccount->id,
                'credit_account_id' => $creditAccount->id,
                'amount' => $amount,
                'memo' => $memo,
                'status' => 'posted',
                'posted_at' => now(),
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
            ]);

            $this->postToGeneralLedger($journal);

            return $journal;
        });
    }

    private function resolveMapping(string $sourceModule, string $transactionType): GlAccountMapping
    {
        $candidate = $transactionType;

        while ($candidate !== '') {
            $mapping = GlAccountMapping::query()
                ->with(['debitAccount', 'creditAccount'])
                ->where('source_module', $sourceModule)
                ->where('transaction_type', $candidate)
                ->where('is_active', true)
                ->first();

            if ($mapping && $mapping->debitAccount && $mapping->creditAccount) {
                return $mapping;
            }

            if (! str_contains($candidate, '-')) {
                break;
            }

            $candidate = substr($candidate, 0, (int) strrpos($candidate, '-'));
        }

        throw new RuntimeException("Mapping akun aktif tidak ditemukan untuk {$sourceModule} / {$transactionType}.");
    }

    private function resolvePeriod(
        Carbon $entryDate,
        ?int $periodId = null,
        bool $allowDraft = false
    ): GlPeriod {
        $allowedStatuses = $allowDraft ? ['draft', 'open'] : ['open'];

        $period = $periodId
            ? GlPeriod::query()->findOrFail($periodId)
            : GlPeriod::query()
                ->whereIn('status', $allowedStatuses)
                ->whereDate('start_date', '<=', $entryDate->toDateString())
                ->whereDate('end_date', '>=', $entryDate->toDateString())
                ->orderByDesc(DB::raw("CASE WHEN status = 'open' THEN 1 ELSE 0 END"))
                ->orderByDesc('start_date')
                ->first();

        if (! $period) {
            throw new RuntimeException('Tidak ada periode akuntansi aktif yang mencakup tanggal transaksi ini.');
        }

        if (! in_array($period->status, $allowedStatuses, true)) {
            throw new RuntimeException('Periode akuntansi untuk transaksi ini tidak tersedia untuk dipakai.');
        }

        if ($entryDate->lt($period->start_date) || $entryDate->gt($period->end_date)) {
            throw new RuntimeException('Tanggal jurnal berada di luar rentang periode akuntansi yang dipilih.');
        }

        return $period;
    }

    private function generateReferenceNumber(string $prefix): string
    {
        $safePrefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $prefix) ?: 'JRN');
        $datePrefix = now()->format('Ymd');
        $count = JournalEntry::query()->whereDate('created_at', now()->toDateString())->count() + 1;

        return $safePrefix . '-' . $datePrefix . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
