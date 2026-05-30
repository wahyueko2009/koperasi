<?php

namespace Tests\Feature;

use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\FixedAssetController;
use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\FixedAssetMutation;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FixedAssetModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_store_can_auto_post_acquisition_journal(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $request = Request::create('/fixed-aset-depresiasi/fixed-aset', 'POST', [
            'asset_code' => 'FA-TEST-001',
            'asset_name' => 'Mobil Test',
            'category' => 'Kendaraan',
            'acquisition_date' => now()->toDateString(),
            'in_service_date' => now()->toDateString(),
            'supplier_name' => 'Dealer Test',
            'location' => 'Kantor Pusat',
            'condition_status' => 'baik',
            'status' => 'active',
            'acquisition_value' => 120000000,
            'residual_value' => 20000000,
            'useful_life_months' => 60,
            'asset_account_id' => ChartOfAccount::where('code', '1501')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1591')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5101')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'auto_post_acquisition' => 1,
        ]);

        $response = app(FixedAssetController::class)->storeAsset($request);

        $this->assertSame(route('fixed-assets.assets'), $response->getTargetUrl());
        $this->assertDatabaseHas('fixed_assets', [
            'asset_code' => 'FA-TEST-001',
            'asset_name' => 'Mobil Test',
        ]);

        $asset = FixedAsset::where('asset_code', 'FA-TEST-001')->firstOrFail();
        $journal = JournalEntry::where('reference_type', 'fixed_asset')
            ->where('reference_id', $asset->id)
            ->where('reference_number', 'like', 'FAA-%')
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('1501', $journal->debitAccount->code);
        $this->assertSame('1001', $journal->creditAccount->code);
        $this->assertSame('fixed-assets', $journal->source_module);
        $this->assertSame(2, $journal->generalLedgerEntries()->count());
    }

    public function test_asset_can_be_updated_from_register(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $asset = FixedAsset::create([
            'asset_code' => 'FA-UPD-001',
            'asset_name' => 'Printer Lama',
            'category' => 'Peralatan',
            'acquisition_date' => now()->subMonths(3)->toDateString(),
            'in_service_date' => now()->subMonths(3)->toDateString(),
            'supplier_name' => 'Vendor Lama',
            'location' => 'Ruang Admin',
            'condition_status' => 'cukup',
            'status' => 'active',
            'acquisition_value' => 6000000,
            'residual_value' => 500000,
            'useful_life_months' => 36,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => ChartOfAccount::where('code', '1502')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1592')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5102')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'is_active' => true,
        ]);

        $request = Request::create('/fixed-aset-depresiasi/fixed-aset/' . $asset->id, 'PUT', [
            'asset_code' => 'FA-UPD-001',
            'asset_name' => 'Printer Baru',
            'category' => 'Inventaris Kantor',
            'acquisition_date' => $asset->acquisition_date->toDateString(),
            'in_service_date' => $asset->in_service_date->toDateString(),
            'supplier_name' => 'Vendor Baru',
            'location' => 'Ruang Finance',
            'condition_status' => 'baik',
            'status' => 'maintenance',
            'acquisition_value' => 6500000,
            'residual_value' => 750000,
            'useful_life_months' => 48,
            'asset_account_id' => ChartOfAccount::where('code', '1502')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1592')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5102')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1002')->value('id'),
            'notes' => 'Dipindah ke finance',
            'is_active' => 1,
        ]);

        $response = app(FixedAssetController::class)->updateAsset($request, $asset);

        $this->assertSame(route('fixed-assets.assets'), $response->getTargetUrl());
        $this->assertDatabaseHas('fixed_assets', [
            'id' => $asset->id,
            'asset_name' => 'Printer Baru',
            'category' => 'Inventaris Kantor',
            'supplier_name' => 'Vendor Baru',
            'location' => 'Ruang Finance',
            'status' => 'maintenance',
        ]);
    }

    public function test_depreciation_run_can_post_journal_for_period(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $asset = FixedAsset::create([
            'asset_code' => 'FA-DEP-001',
            'asset_name' => 'Mesin Fotocopy',
            'category' => 'Peralatan',
            'acquisition_date' => now()->startOfMonth()->toDateString(),
            'in_service_date' => now()->startOfMonth()->toDateString(),
            'location' => 'Ruang Produksi',
            'condition_status' => 'baik',
            'status' => 'active',
            'acquisition_value' => 24000000,
            'residual_value' => 0,
            'useful_life_months' => 24,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => ChartOfAccount::where('code', '1502')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1592')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5102')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'is_active' => true,
        ]);

        $request = Request::create('/fixed-aset-depresiasi/depresiasi', 'POST', [
            'month' => now()->month,
            'year' => now()->year,
            'post_to_journal' => 1,
        ]);

        $response = app(FixedAssetController::class)->runDepreciation($request);

        $this->assertSame(route('fixed-assets.depreciation', [
            'year' => now()->year,
            'month' => now()->month,
        ]), $response->getTargetUrl());

        $depreciation = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)->first();

        $this->assertNotNull($depreciation);
        $this->assertSame('posted', $depreciation->status);
        $this->assertSame(1000000.0, (float) $depreciation->amount);
        $this->assertNotNull($depreciation->journal_entry_id);

        $journal = JournalEntry::findOrFail($depreciation->journal_entry_id);
        $this->assertSame('5102', $journal->debitAccount->code);
        $this->assertSame('1592', $journal->creditAccount->code);
        $this->assertSame(2, $journal->generalLedgerEntries()->count());
    }

    public function test_disposal_mutation_can_auto_post_disposal_batch(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $asset = FixedAsset::create([
            'asset_code' => 'FA-DSP-001',
            'asset_name' => 'Mobil Operasional Lama',
            'category' => 'Kendaraan',
            'acquisition_date' => now()->subYears(2)->startOfMonth()->toDateString(),
            'in_service_date' => now()->subYears(2)->startOfMonth()->toDateString(),
            'location' => 'Garasi',
            'condition_status' => 'cukup',
            'status' => 'active',
            'acquisition_value' => 12000000,
            'residual_value' => 0,
            'useful_life_months' => 60,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => ChartOfAccount::where('code', '1501')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1591')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5101')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'is_active' => true,
        ]);

        FixedAssetDepreciation::create([
            'fixed_asset_id' => $asset->id,
            'period_year' => now()->subMonth()->year,
            'period_month' => now()->subMonth()->month,
            'depreciation_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'amount' => 2000000,
            'accumulated_amount' => 2000000,
            'book_value' => 10000000,
            'status' => 'posted',
        ]);

        $request = Request::create('/fixed-aset-depresiasi/mutasi-aset', 'POST', [
            'fixed_asset_id' => $asset->id,
            'mutation_date' => now()->toDateString(),
            'mutation_type' => 'disposal',
            'to_status' => 'disposed',
            'disposal_value' => 7000000,
            'disposal_debit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'disposal_gain_account_id' => ChartOfAccount::where('code', '4105')->value('id'),
            'disposal_loss_account_id' => ChartOfAccount::where('code', '5004')->value('id'),
            'auto_post_disposal' => 1,
            'notes' => 'Jual aset lama',
        ]);

        $response = app(FixedAssetController::class)->storeMutation($request);

        $this->assertSame(route('fixed-assets.mutations'), $response->getTargetUrl());

        $mutation = FixedAssetMutation::where('fixed_asset_id', $asset->id)->first();

        $this->assertNotNull($mutation);
        $this->assertNotNull($mutation->disposal_reference_number);
        $this->assertSame('disposed', $asset->fresh()->status);
        $this->assertFalse((bool) $asset->fresh()->is_active);

        $journals = JournalEntry::where('reference_type', 'fixed_asset')
            ->where('reference_id', $asset->id)
            ->where('reference_number', 'like', $mutation->disposal_reference_number . '%')
            ->orderBy('reference_number')
            ->get();

        $this->assertCount(3, $journals);
        $this->assertSame(['1591', '1001', '5004'], $journals->pluck('debitAccount.code')->all());
        $this->assertTrue($journals->every(fn (JournalEntry $journal) => $journal->generalLedgerEntries()->count() === 2));
    }

    public function test_fixed_asset_reports_page_is_accessible(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $request = Request::create('/fixed-aset-depresiasi/laporan-fa', 'GET', [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $view = app(FixedAssetController::class)->reports($request);

        $this->assertSame('Laporan Fixed Aset', $view->getData()['pageTitle']);
        $this->assertArrayHasKey('categorySummary', $view->getData());
    }

    public function test_fixed_asset_reports_can_be_exported_to_excel(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        FixedAsset::create([
            'asset_code' => 'FA-RPT-001',
            'asset_name' => 'Laptop Operasional',
            'category' => 'Peralatan',
            'acquisition_date' => now()->subMonths(2)->toDateString(),
            'in_service_date' => now()->subMonths(2)->toDateString(),
            'location' => 'Ruang IT',
            'condition_status' => 'baik',
            'status' => 'active',
            'acquisition_value' => 15000000,
            'residual_value' => 1000000,
            'useful_life_months' => 36,
            'depreciation_method' => 'straight_line',
            'asset_account_id' => ChartOfAccount::where('code', '1502')->value('id'),
            'accumulated_depreciation_account_id' => ChartOfAccount::where('code', '1592')->value('id'),
            'depreciation_expense_account_id' => ChartOfAccount::where('code', '5102')->value('id'),
            'acquisition_credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'is_active' => true,
        ]);

        $request = Request::create('/fixed-aset-depresiasi/laporan-fa/export/excel', 'GET', [
            'month' => now()->month,
            'year' => now()->year,
        ]);

        $response = app(FixedAssetController::class)->exportReportsExcel($request);

        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
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
