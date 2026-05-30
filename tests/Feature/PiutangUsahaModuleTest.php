<?php

namespace Tests\Feature;

use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\PiutangUsahaController;
use App\Models\JournalEntry;
use App\Models\PiutangUsahaCompany;
use App\Models\PiutangUsahaContract;
use App\Models\PiutangUsahaContractItem;
use App\Models\PiutangUsahaInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PiutangUsahaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_invoice_creates_gl_journal(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $company = PiutangUsahaCompany::create([
            'code' => 'CUS-001',
            'name' => 'PT Contoh Jaya',
            'payment_term_days' => 30,
            'is_active' => true,
        ]);

        $contract = PiutangUsahaContract::create([
            'contract_number' => 'CTR-001',
            'company_id' => $company->id,
            'contract_date' => now()->toDateString(),
            'start_date' => now()->startOfMonth()->toDateString(),
            'service_category' => 'Outsourcing',
            'billing_cycle' => 'bulanan',
            'payment_term_days' => 30,
            'total_amount' => 2500000,
            'status' => 'active',
        ]);

        PiutangUsahaContractItem::create([
            'contract_id' => $contract->id,
            'item_name' => 'Jasa Driver',
            'quantity' => 1,
            'unit' => 'paket',
            'unit_price' => 2500000,
            'subtotal' => 2500000,
        ]);

        $request = Request::create('/piutang-usaha/tagihan-invoice', 'POST', [
            'invoice_number' => 'INV-001',
            'contract_id' => $contract->id,
            'invoice_date' => now()->toDateString(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'payment_term_days' => 30,
            'status' => 'issued',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(PiutangUsahaController::class)->storeInvoice($request);

        $this->assertSame(route('piutang-usaha.invoices'), $response->getTargetUrl());

        $invoice = PiutangUsahaInvoice::where('invoice_number', 'INV-001')->firstOrFail();
        $journal = JournalEntry::find($invoice->journal_entry_id);

        $this->assertNotNull($journal);
        $this->assertSame('piutang-usaha', $journal->source_module);
        $this->assertSame('1102', $journal->debitAccount->code);
        $this->assertSame('4104', $journal->creditAccount->code);
    }

    public function test_receivable_payment_creates_gl_journal_and_updates_invoice(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $company = PiutangUsahaCompany::create([
            'code' => 'CUS-002',
            'name' => 'PT Bayar Lancar',
            'payment_term_days' => 30,
            'is_active' => true,
        ]);

        $contract = PiutangUsahaContract::create([
            'contract_number' => 'CTR-002',
            'company_id' => $company->id,
            'contract_date' => now()->toDateString(),
            'start_date' => now()->startOfMonth()->toDateString(),
            'service_category' => 'Outsourcing',
            'billing_cycle' => 'bulanan',
            'payment_term_days' => 30,
            'total_amount' => 3000000,
            'status' => 'active',
        ]);

        $invoice = PiutangUsahaInvoice::create([
            'invoice_number' => 'INV-002',
            'contract_id' => $contract->id,
            'company_id' => $company->id,
            'invoice_date' => now()->toDateString(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'payment_term_days' => 30,
            'total_amount' => 3000000,
            'paid_amount' => 0,
            'outstanding_amount' => 3000000,
            'status' => 'issued',
        ]);

        $request = Request::create('/piutang-usaha/pembayaran-piutang', 'POST', [
            'payment_number' => 'PAY-001',
            'invoice_id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'amount' => 1000000,
            'payment_method' => 'transfer',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(PiutangUsahaController::class)->storePayment($request);

        $this->assertSame(route('piutang-usaha.payments'), $response->getTargetUrl());

        $updatedInvoice = $invoice->fresh();
        $payment = $updatedInvoice->payments()->firstOrFail();
        $journal = JournalEntry::find($payment->journal_entry_id);

        $this->assertSame(1000000.0, (float) $updatedInvoice->paid_amount);
        $this->assertSame(2000000.0, (float) $updatedInvoice->outstanding_amount);
        $this->assertSame('partial', $updatedInvoice->status);
        $this->assertNotNull($journal);
        $this->assertSame('1002', $journal->debitAccount->code);
        $this->assertSame('1102', $journal->creditAccount->code);
    }

    private function signInAsAdministrator(): User
    {
        $user = User::factory()->create([
            'role' => 'administrator',
            'is_active' => true,
        ]);

        $this->withSession(['login_context' => 'pengurus'])->actingAs($user);

        return $user;
    }

    private function seedGeneralLedgerDefaults(): void
    {
        app(GeneralLedgerController::class)->setupDefaults();
    }
}
