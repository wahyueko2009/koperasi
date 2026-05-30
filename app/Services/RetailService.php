<?php

namespace App\Services;

use App\Models\Member;
use App\Models\RetailTransaction;
use App\Models\RetailTransactionItem;
use App\Models\RetailItem;
use App\Models\UnitUsahaSale;
use App\Models\UnitUsahaSaleItem;
use Illuminate\Support\Facades\DB;

class RetailService
{
    public function __construct(
        private JournalService $journalService,
        private RetailInventoryBridgeService $retailInventoryBridgeService
    ) {}

    /**
     * Catat transaksi retail dan buat jurnal otomatis
     *
     * Untuk pembayaran Tunai:
     * Debet: Kas (1001)
     * Kredit: Pendapatan Retail (4102 atau 4103)
     *
     * Untuk pembayaran Potong Gaji (Piutang):
     * Debet: Piutang Anggota (1102)
     * Kredit: Pendapatan Retail (4102 atau 4103)
     */
    public function recordTransaction(
        ?int $memberId,
        string $category,
        array $items,
        string $paymentMethod = 'cash',
        string $notes = null
    ): RetailTransaction {
        return DB::transaction(function () use ($memberId, $category, $items, $paymentMethod, $notes) {
            $mappingPaymentMethod = str_replace('_', '-', $paymentMethod);
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['subtotal'];
            }

            $transaction = RetailTransaction::create([
                'member_id' => $memberId,
                'category' => $category,
                'transaction_date' => now(),
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'notes' => $notes,
                'is_posted' => false,
            ]);

            foreach ($items as $item) {
                $retailItem = RetailItem::query()->lockForUpdate()->findOrFail($item['retail_item_id']);
                $inventory = $this->retailInventoryBridgeService->syncInventoryFromRetailItem($retailItem);

                if ((int) $retailItem->stock < (int) $item['quantity']) {
                    throw new \RuntimeException('Stok item retail ' . $retailItem->name . ' tidak mencukupi.');
                }

                $retailItem->decrement('stock', (int) $item['quantity']);
                $inventory->decrement('stock', (int) $item['quantity']);

                RetailTransactionItem::create([
                    'retail_transaction_id' => $transaction->id,
                    'retail_item_id' => $retailItem->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $journal = null;
            if ($paymentMethod === 'cash') {
                $journal = $this->journalService->createMappedJournal(
                    sourceModule: 'unit-usaha',
                    transactionType: "retail-sale-{$category}-{$mappingPaymentMethod}",
                    referenceType: 'retail_transaction',
                    referenceId: $transaction->id,
                    amount: $totalAmount,
                    memo: "Penjualan Retail $category - Tunai"
                );
            } elseif ($paymentMethod === 'salary_cut' && $memberId) {
                $journal = $this->journalService->createMappedJournal(
                    sourceModule: 'unit-usaha',
                    transactionType: "retail-sale-{$category}-{$mappingPaymentMethod}",
                    referenceType: 'retail_transaction',
                    referenceId: $transaction->id,
                    amount: $totalAmount,
                    memo: "Penjualan Retail $category - Piutang Anggota ID: {$memberId}"
                );

                // Update member piutang
                $member = Member::findOrFail($memberId);
                $member->increment('balance_receivable', $totalAmount);

                // Update member ledger
                $this->journalService->updateMemberLedger(
                    memberId: $memberId,
                    ledgerScope: 'retail_receivable',
                    transactionType: 'retail_transaction',
                    transactionId: $transaction->id,
                    debit: $totalAmount,
                    credit: 0,
                    memo: "Pembelian Retail $category"
                );
            }

            $sale = UnitUsahaSale::create([
                'sale_number' => 'RTL-' . now()->format('Ymd') . '-' . str_pad((string) $transaction->id, 4, '0', STR_PAD_LEFT),
                'sale_date' => $transaction->transaction_date,
                'member_id' => $memberId,
                'category' => $category,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'total_amount' => $totalAmount,
                'status' => 'posted',
                'is_posted' => true,
                'journal_entry_id' => $journal?->id,
                'source_module' => 'retail-api',
                'source_reference_type' => 'retail_transaction',
                'source_reference_id' => $transaction->id,
            ]);

            foreach ($transaction->items()->with('item')->get() as $transactionItem) {
                UnitUsahaSaleItem::create([
                    'sale_id' => $sale->id,
                    'item_type' => 'retail',
                    'retail_item_id' => $transactionItem->retail_item_id,
                    'item_name' => $transactionItem->item?->name ?? 'Item Retail',
                    'quantity' => $transactionItem->quantity,
                    'unit_price' => $transactionItem->unit_price,
                    'subtotal' => $transactionItem->subtotal,
                ]);
            }

            $transaction->update(['is_posted' => true]);

            return $transaction->fresh();
        });
    }
}
