<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Member;
use App\Models\RetailItem;
use App\Models\Saving;
use App\Models\SavingType;
use App\Services\LoanService;
use App\Services\RetailService;
use App\Services\SavingService;
use Illuminate\Console\Command;

class GenerateTestTransactions extends Command
{
    protected $signature = 'app:generate-test-transactions';
    protected $description = 'Generate sample transactions for testing';

    public function __construct(
        private SavingService $savingService,
        private LoanService $loanService,
        private RetailService $retailService,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Generating test transactions...');

        // Get test members
        $members = Member::limit(3)->get();
        $savingTypes = SavingType::all();

        foreach ($members as $member) {
            // Create savings
            $this->info("Creating savings for {$member->name}...");
            foreach ($savingTypes as $type) {
                $this->savingService->recordSaving(
                    memberId: $member->id,
                    savingTypeId: $type->id,
                    amount: 500000,
                    paymentMethod: 'cash',
                    notes: "Simpanan {$type->name} untuk testing"
                );
            }

            // Create loan
            $this->info("Creating loan for {$member->name}...");
            $loan = Loan::create([
                'member_id' => $member->id,
                'principal_amount' => 5000000,
                'interest_rate' => 2.5,
                'tenor_months' => 12,
                'approval_date' => now(),
                'maturity_date' => now()->addMonths(12),
                'remaining_balance' => 5000000,
                'monthly_payment' => 438000,
                'status' => 'approved',
            ]);

            // Disburse loan
            $this->loanService->disburse($loan->id);
            $this->info("Loan disbursed for {$member->name}");

            // Make loan payment
            $this->info("Creating loan payment for {$member->name}...");
            $this->loanService->recordLoanPayment(
                loanId: $loan->id,
                principalPaid: 400000,
                interestPaid: 38000,
                paymentMethod: 'salary_cut',
                notes: 'Pembayaran cicilan pinjaman - testing'
            );

            // Create retail transactions
            $this->info("Creating retail transactions for {$member->name}...");
            $retailItems = RetailItem::limit(3)->get();
            $items = $retailItems->map(fn($item) => [
                'retail_item_id' => $item->id,
                'quantity' => 2,
                'unit_price' => $item->price,
                'subtotal' => $item->price * 2,
            ])->toArray();

            $this->retailService->recordTransaction(
                memberId: $member->id,
                category: 'indomaret',
                items: $items,
                paymentMethod: 'salary_cut',
                notes: 'Pembelian retail - testing'
            );

            $this->retailService->recordTransaction(
                memberId: $member->id,
                category: 'photocopy',
                items: [
                    [
                        'retail_item_id' => RetailItem::where('category', 'photocopy')->first()->id,
                        'quantity' => 500,
                        'unit_price' => 150,
                        'subtotal' => 75000,
                    ]
                ],
                paymentMethod: 'cash',
                notes: 'Fotocopy - testing'
            );
        }

        $this->info('✅ All test transactions generated successfully!');

        // Show summary
        $this->line('');
        $this->info('=== TRANSACTION SUMMARY ===');
        $this->line('Members: ' . Member::count());
        $this->line('Savings: ' . Saving::count());
        $this->line('Loans: ' . Loan::count());
    }
}
