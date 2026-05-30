<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberReceivablePayment;
use Illuminate\Support\Facades\DB;

class MemberReceivableService
{
    public function __construct(private JournalService $journalService) {}

    public function recordPayment(
        int $memberId,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        ?string $paymentDate = null
    ): MemberReceivablePayment {
        return DB::transaction(function () use ($memberId, $amount, $paymentMethod, $notes, $paymentDate) {
            $member = Member::query()->lockForUpdate()->findOrFail($memberId);

            if ($member->status !== 'active') {
                throw new \RuntimeException('Anggota tidak aktif.');
            }

            $currentBalance = (float) $member->balance_receivable;
            if ($currentBalance <= 0) {
                throw new \RuntimeException('Anggota ini tidak memiliki piutang retail yang harus dilunasi.');
            }

            if ($amount > $currentBalance) {
                throw new \RuntimeException('Nominal pelunasan melebihi saldo piutang retail anggota.');
            }

            $payment = MemberReceivablePayment::create([
                'member_id' => $member->id,
                'payment_number' => $this->generatePaymentNumber(),
                'payment_date' => $paymentDate ?: now()->toDateString(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'journal_entry_id' => null,
            ]);

            $debitAccountCode = $paymentMethod === 'transfer' ? '1002' : '1001';
            $journal = $this->journalService->createJournal(
                referenceType: 'member_receivable_payment',
                referenceId: $payment->id,
                debitAccountCode: $debitAccountCode,
                creditAccountCode: '1101',
                amount: $amount,
                memo: 'Pelunasan piutang retail anggota ' . $member->name
            );

            $member->update([
                'balance_receivable' => max(0, $currentBalance - $amount),
            ]);

            $this->journalService->updateMemberLedger(
                memberId: $member->id,
                ledgerScope: 'retail_receivable',
                transactionType: 'member_receivable_payment',
                transactionId: $payment->id,
                debit: 0,
                credit: $amount,
                memo: 'Pelunasan piutang retail anggota'
            );

            $payment->update([
                'journal_entry_id' => $journal->id,
            ]);

            return $payment->fresh();
        });
    }

    private function generatePaymentNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = MemberReceivablePayment::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'RCP-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }
}
