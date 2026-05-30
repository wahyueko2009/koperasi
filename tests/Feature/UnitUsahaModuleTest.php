<?php

namespace Tests\Feature;

use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\MemberWebController;
use App\Http\Controllers\UnitUsahaController;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MemberReceivablePayment;
use App\Models\MemberLedger;
use App\Models\RetailItem;
use App\Models\RetailTransaction;
use App\Models\UnitUsahaInventory;
use App\Models\UnitUsahaPurchase;
use App\Models\UnitUsahaSale;
use App\Models\UnitUsahaStockOpname;
use App\Models\User;
use App\Services\MemberReceivableService;
use App\Services\RetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UnitUsahaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_sale_with_salary_cut_updates_stock_receivable_and_gl(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = Member::create([
            'nik' => 'M-900',
            'name' => 'Anggota POS',
            'email' => 'pos@example.com',
            'status' => 'active',
            'balance_receivable' => 0,
        ]);

        $inventory = UnitUsahaInventory::create([
            'code' => 'INV-001',
            'name' => 'Kertas A4',
            'category' => 'ATK',
            'unit' => 'rim',
            'stock' => 10,
            'minimum_stock' => 2,
            'purchase_price' => 50000,
            'selling_price' => 65000,
            'is_active' => true,
        ]);

        $request = Request::create('/unit-usaha/kasir-pos', 'POST', [
            'sale_number' => 'POS-TEST-001',
            'sale_date' => now()->toDateString(),
            'category' => 'photocopy',
            'payment_method' => 'salary_cut',
            'member_id' => $member->id,
            'items' => [
                [
                    'item_type' => 'inventory',
                    'item_id' => $inventory->id,
                    'quantity' => 2,
                    'unit_price' => 65000,
                ],
            ],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(UnitUsahaController::class)->storePos($request);

        $this->assertSame(route('unit-usaha.pos'), $response->getTargetUrl());

        $sale = UnitUsahaSale::where('sale_number', 'POS-TEST-001')->firstOrFail();
        $journal = JournalEntry::findOrFail($sale->journal_entry_id);

        $this->assertTrue((bool) $sale->is_posted);
        $this->assertSame(130000.0, (float) $sale->total_amount);
        $this->assertSame(8, (int) $inventory->fresh()->stock);
        $this->assertSame(130000.0, (float) $member->fresh()->balance_receivable);
        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'transaction_type' => 'unit_usaha_sale',
            'transaction_id' => $sale->id,
        ]);
        $this->assertSame('1101', $journal->debitAccount->code);
        $this->assertSame('4103', $journal->creditAccount->code);
        $this->assertSame('unit-usaha', $journal->source_module);
        $this->assertSame(2, $journal->generalLedgerEntries()->count());
    }

    public function test_retail_service_updates_retail_stock_and_projects_to_unit_usaha_sales(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = Member::create([
            'nik' => 'M-901',
            'name' => 'Anggota Retail',
            'email' => 'retail@example.com',
            'status' => 'active',
            'balance_receivable' => 0,
        ]);

        $item = RetailItem::create([
            'sku' => 'RTL-001',
            'name' => 'Pulpen',
            'unit' => 'pcs',
            'price' => 5000,
            'category' => 'indomaret',
            'stock' => 20,
            'is_active' => true,
        ]);

        $transaction = app(RetailService::class)->recordTransaction(
            memberId: $member->id,
            category: 'indomaret',
            items: [[
                'retail_item_id' => $item->id,
                'quantity' => 3,
                'unit_price' => 5000,
                'subtotal' => 15000,
            ]],
            paymentMethod: 'salary_cut',
            notes: 'Tes retail API'
        );

        $sale = UnitUsahaSale::where('source_reference_type', 'retail_transaction')
            ->where('source_reference_id', $transaction->id)
            ->firstOrFail();

        $this->assertTrue((bool) $transaction->fresh()->is_posted);
        $this->assertSame(17, (int) $item->fresh()->stock);
        $this->assertSame('retail-api', $sale->source_module);
        $this->assertSame(15000.0, (float) $sale->total_amount);
        $this->assertSame(1, $sale->items()->count());
        $this->assertNotNull($item->fresh()->linked_inventory_id);
        $this->assertSame(17, (int) $item->fresh()->linkedInventory->stock);
    }

    public function test_purchase_posts_journal_and_syncs_linked_retail_inventory(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $inventory = UnitUsahaInventory::create([
            'code' => 'RTL-SYNC-001',
            'name' => 'Snack Retail',
            'category' => 'INDOMARET',
            'unit' => 'pcs',
            'stock' => 5,
            'minimum_stock' => 1,
            'purchase_price' => 3000,
            'selling_price' => 5000,
            'is_active' => true,
        ]);

        $retailItem = RetailItem::create([
            'sku' => 'SYNC-001',
            'name' => 'Snack Retail',
            'unit' => 'pcs',
            'price' => 5000,
            'category' => 'indomaret',
            'stock' => 5,
            'is_active' => true,
            'linked_inventory_id' => $inventory->id,
        ]);

        $request = Request::create('/unit-usaha/pembelian-barang-masuk', 'POST', [
            'purchase_number' => 'PBM-TEST-001',
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'credit',
            'supplier_name' => 'Supplier A',
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'quantity' => 4,
                    'unit_price' => 3200,
                ],
            ],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(UnitUsahaController::class)->storePurchase($request);

        $this->assertSame(route('unit-usaha.purchases'), $response->getTargetUrl());

        $purchase = UnitUsahaPurchase::where('purchase_number', 'PBM-TEST-001')->firstOrFail();
        $journal = JournalEntry::findOrFail($purchase->journal_entry_id);

        $this->assertTrue((bool) $purchase->is_posted);
        $this->assertSame('credit', $purchase->payment_method);
        $this->assertSame(12800.0, (float) $purchase->total_amount);
        $this->assertSame(9, (int) $inventory->fresh()->stock);
        $this->assertSame(9, (int) $retailItem->fresh()->stock);
        $this->assertSame('1201', $journal->debitAccount->code);
        $this->assertSame('2001', $journal->creditAccount->code);
    }

    public function test_stock_opname_posts_adjustment_journal_and_syncs_linked_retail_inventory(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $inventory = UnitUsahaInventory::create([
            'code' => 'RTL-SYNC-002',
            'name' => 'Kertas Thermal',
            'category' => 'PHOTOCOPY',
            'unit' => 'roll',
            'stock' => 10,
            'minimum_stock' => 2,
            'purchase_price' => 4000,
            'selling_price' => 6000,
            'is_active' => true,
        ]);

        $retailItem = RetailItem::create([
            'sku' => 'SYNC-002',
            'name' => 'Kertas Thermal',
            'unit' => 'roll',
            'price' => 6000,
            'category' => 'photocopy',
            'stock' => 10,
            'is_active' => true,
            'linked_inventory_id' => $inventory->id,
        ]);

        $request = Request::create('/unit-usaha/stock-opname', 'POST', [
            'opname_number' => 'OPN-TEST-001',
            'opname_date' => now()->toDateString(),
            'items' => [
                [
                    'inventory_id' => $inventory->id,
                    'physical_stock' => 7,
                ],
            ],
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(UnitUsahaController::class)->storeStockOpname($request);

        $this->assertSame(route('unit-usaha.stock-opname'), $response->getTargetUrl());

        $opname = UnitUsahaStockOpname::where('opname_number', 'OPN-TEST-001')->firstOrFail();
        $journal = JournalEntry::findOrFail($opname->journal_entry_id);

        $this->assertTrue((bool) $opname->is_posted);
        $this->assertSame(-12000.0, (float) $opname->adjustment_value);
        $this->assertSame(7, (int) $inventory->fresh()->stock);
        $this->assertSame(7, (int) $retailItem->fresh()->stock);
        $this->assertSame('5002', $journal->debitAccount->code);
        $this->assertSame('1201', $journal->creditAccount->code);
    }

    public function test_member_retail_receivable_payment_reduces_balance_and_posts_journal(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = Member::create([
            'nik' => 'M-902',
            'name' => 'Anggota Piutang Retail',
            'email' => 'receivable@example.com',
            'status' => 'active',
            'balance_receivable' => 50000,
        ]);

        MemberLedger::create([
            'member_id' => $member->id,
            'ledger_scope' => 'retail_receivable',
            'entry_date' => now(),
            'transaction_type' => 'unit_usaha_sale',
            'transaction_id' => 1,
            'debit' => 50000,
            'credit' => 0,
            'balance' => 50000,
            'memo' => 'Saldo awal piutang retail',
        ]);

        $request = Request::create('/members/' . $member->id . '/retail-receivable-payments', 'POST', [
            'amount' => 20000,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'notes' => 'Pelunasan sebagian',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(MemberWebController::class)->storeReceivablePayment($request, $member);

        $this->assertSame(route('members', ['selected' => $member->id]), $response->getTargetUrl());

        $payment = MemberReceivablePayment::where('member_id', $member->id)->firstOrFail();
        $journal = JournalEntry::findOrFail($payment->journal_entry_id);

        $this->assertSame(30000.0, (float) $member->fresh()->balance_receivable);
        $this->assertSame('1001', $journal->debitAccount->code);
        $this->assertSame('1101', $journal->creditAccount->code);
        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'transaction_type' => 'member_receivable_payment',
            'transaction_id' => $payment->id,
        ]);
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
