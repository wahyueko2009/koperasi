<?php

namespace App\Services;

use App\Models\Saving;
use Illuminate\Support\Facades\DB;

class SavingService
{
    public function __construct(private JournalService $journalService) {}

    /**
     * Catat simpanan baru dan buat jurnal otomatis
     *
     * Jurnal yang dibuat:
     * Debet: Kas (1001)
     * Kredit: Hutang Simpanan Anggota (2101)
     */
    public function recordSaving(
        int $memberId,
        int $savingTypeId,
        float $amount,
        string $paymentMethod = 'cash',
        string $notes = null
    ): Saving {
        return DB::transaction(function () use ($memberId, $savingTypeId, $amount, $paymentMethod, $notes) {
            $mappingPaymentMethod = str_replace('_', '-', $paymentMethod);

            $saving = Saving::create([
                'member_id' => $memberId,
                'saving_type_id' => $savingTypeId,
                'deposit_date' => now(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'is_posted' => false,
            ]);

            $this->journalService->createMappedJournal(
                sourceModule: 'simpan-pinjam',
                transactionType: 'saving-deposit-' . $mappingPaymentMethod,
                referenceType: 'savings',
                referenceId: $saving->id,
                amount: $amount,
                memo: "Setoran Simpanan Anggota ID: {$memberId}"
            );

            $this->journalService->updateMemberLedger(
                memberId: $memberId,
                ledgerScope: 'savings',
                transactionType: 'savings',
                transactionId: $saving->id,
                debit: $amount,
                credit: 0,
                memo: 'Setoran Simpanan'
            );

            $saving->update(['is_posted' => true]);

            return $saving->fresh();
        });
    }
}
