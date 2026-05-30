<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function __construct(private JournalService $journalService) {}

    /**
     * Catat pembayaran pinjaman dan buat jurnal otomatis
     *
     * Jurnal untuk pembayaran pokok:
     * Debet: Kas (1001)
     * Kredit: Piutang Anggota (1102)
     *
     * Jurnal untuk pembayaran bunga:
     * Debet: Kas (1001)
     * Kredit: Pendapatan Bunga (4101)
     */
    public function recordLoanPayment(
        int $loanId,
        float $principalPaid,
        float $interestPaid,
        string $paymentMethod = 'salary_cut',
        string $notes = null
    ): LoanPayment {
        return DB::transaction(function () use ($loanId, $principalPaid, $interestPaid, $paymentMethod, $notes) {
            $loan = Loan::lockForUpdate()->findOrFail($loanId);
            $mappingPaymentMethod = str_replace('_', '-', $paymentMethod);

            if (! in_array($loan->status, ['disbursed', 'active'], true)) {
                throw new \Exception('Pinjaman harus sudah dicairkan sebelum menerima pembayaran');
            }

            $totalPaid = $principalPaid + $interestPaid;

            $payment = LoanPayment::create([
                'loan_id' => $loanId,
                'payment_date' => now(),
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
                'total_paid' => $totalPaid,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'is_posted' => false,
            ]);

            if ($principalPaid > 0) {
                $this->journalService->createMappedJournal(
                    sourceModule: 'simpan-pinjam',
                    transactionType: 'loan-payment-principal-' . $mappingPaymentMethod,
                    referenceType: 'loan_payment',
                    referenceId: $payment->id,
                    amount: $principalPaid,
                    memo: "Pembayaran Pokok Pinjaman - Member ID: {$loan->member_id}"
                );
            }

            if ($interestPaid > 0) {
                $this->journalService->createMappedJournal(
                    sourceModule: 'simpan-pinjam',
                    transactionType: 'loan-payment-interest-' . $mappingPaymentMethod,
                    referenceType: 'loan_payment',
                    referenceId: $payment->id,
                    amount: $interestPaid,
                    memo: "Pembayaran Bunga Pinjaman - Member ID: {$loan->member_id}"
                );
            }

            $this->journalService->updateMemberLedger(
                memberId: $loan->member_id,
                ledgerScope: 'loan_payable',
                transactionType: 'loan_payment',
                transactionId: $payment->id,
                debit: 0,
                credit: $principalPaid,
                memo: 'Pembayaran Pokok Pinjaman'
            );

            $loan->remaining_balance -= $principalPaid;
            if ($loan->remaining_balance <= 0) {
                $loan->status = 'completed';
                $loan->application_status = 'completed';
                $loan->remaining_balance = 0;
            } elseif ($loan->status === 'disbursed') {
                $loan->status = 'active';
            }
            $loan->save();

            $this->syncMemberPayableBalance($loan->member_id);

            $payment->update(['is_posted' => true]);

            return $payment->fresh();
        });
    }

    /**
     * Pencairan pinjaman
     *
     * Jurnal yang dibuat:
     * Debet: Piutang Anggota (1102)
     * Kredit: Kas (1001)
     */
    public function disburse(int $loanId): Loan
    {
        return DB::transaction(function () use ($loanId) {
            $loan = Loan::lockForUpdate()->findOrFail($loanId);

            if ($loan->status !== 'approved') {
                throw new \Exception('Loan must be in approved status to disburse');
            }

            $this->journalService->createMappedJournal(
                sourceModule: 'simpan-pinjam',
                transactionType: 'loan-disbursement',
                referenceType: 'loan_disbursement',
                referenceId: $loan->id,
                amount: (float) $loan->principal_amount,
                memo: "Pencairan Pinjaman - Member ID: {$loan->member_id}"
            );

            $loan->update([
                'status' => 'disbursed',
                'disbursement_date' => now(),
                'application_status' => 'disbursed',
            ]);

            $this->journalService->updateMemberLedger(
                memberId: $loan->member_id,
                ledgerScope: 'loan_payable',
                transactionType: 'loan_disbursement',
                transactionId: $loan->id,
                debit: (float) $loan->principal_amount,
                credit: 0,
                memo: 'Pencairan Pinjaman'
            );

            $this->syncMemberPayableBalance($loan->member_id);

            return $loan->fresh();
        });
    }

    private function syncMemberPayableBalance(int $memberId): void
    {
        $balance = (float) Loan::query()
            ->where('member_id', $memberId)
            ->whereIn('status', ['approved', 'disbursed', 'active'])
            ->sum('remaining_balance');

        Member::query()
            ->whereKey($memberId)
            ->update([
                'balance_payable' => max(0, $balance),
            ]);
    }
}
