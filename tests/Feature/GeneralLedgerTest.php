<?php

namespace Tests\Feature;

use App\Http\Controllers\GeneralLedgerController;
use App\Models\ChartOfAccount;
use App\Models\GlPeriod;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\SavingType;
use App\Models\User;
use App\Services\JournalService;
use App\Services\LoanService;
use App\Services\SavingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GeneralLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_keeps_abnormal_balance_on_opposite_side(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $journalService = app(JournalService::class);

        $journalService->createManualJournal([
            'entry_date' => now()->toDateString(),
            'reference_number' => 'TEST-TB-001',
            'debit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'credit_account_id' => ChartOfAccount::where('code', '2101')->value('id'),
            'amount' => 100,
        ], true);

        $journalService->createManualJournal([
            'entry_date' => now()->toDateString(),
            'reference_number' => 'TEST-TB-002',
            'debit_account_id' => ChartOfAccount::where('code', '2101')->value('id'),
            'credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'amount' => 150,
        ], true);

        $view = app(GeneralLedgerController::class)->trialBalance(new Request());
        $rows = $view->getData()['rows'];

        $cash = $rows->firstWhere('code', '1001');
        $savingLiability = $rows->firstWhere('code', '2101');

        $this->assertNotNull($cash);
        $this->assertNotNull($savingLiability);
        $this->assertSame(0.0, (float) $cash->debit_balance);
        $this->assertSame(50.0, (float) $cash->credit_balance);
        $this->assertSame(50.0, (float) $savingLiability->debit_balance);
        $this->assertSame(0.0, (float) $savingLiability->credit_balance);
    }

    public function test_closing_period_is_blocked_when_draft_journals_still_exist(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $period = GlPeriod::where('status', 'open')->firstOrFail();

        JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'period_id' => $period->id,
            'reference_type' => 'manual',
            'reference_id' => 0,
            'source_module' => 'manual',
            'reference_number' => 'DRAFT-CLOSE-001',
            'debit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'credit_account_id' => ChartOfAccount::where('code', '2101')->value('id'),
            'amount' => 1000,
            'memo' => 'Draft journal before closing',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $request = Request::create('/buku-besar-gl/tutup-buku', 'POST', [
            'period_id' => $period->id,
            'notes' => 'Attempt closing with draft',
        ]);

        $response = app(GeneralLedgerController::class)->processClosing($request);

        $this->assertNotNull($response);
        $this->assertSame('open', $period->fresh()->status);
    }

    public function test_posting_is_blocked_for_journal_in_closed_period(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $period = GlPeriod::where('status', 'open')->firstOrFail();
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        $journal = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'period_id' => $period->id,
            'reference_type' => 'manual',
            'reference_id' => 0,
            'source_module' => 'manual',
            'reference_number' => 'CLOSED-PERIOD-001',
            'debit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'credit_account_id' => ChartOfAccount::where('code', '2101')->value('id'),
            'amount' => 1000,
            'memo' => 'Draft journal in closed period',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        app(GeneralLedgerController::class)->processPosting($journal);
        $this->assertSame('draft', $journal->fresh()->status);
    }

    public function test_saving_service_uses_mapping_and_records_audit_user(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = Member::create([
            'nik' => 'M-001',
            'name' => 'Anggota Test',
            'email' => 'anggota@example.com',
            'status' => 'active',
        ]);

        $savingType = SavingType::create([
            'code' => 'SW',
            'name' => 'Simpanan Wajib',
            'is_mandatory' => true,
            'monthly_amount' => 50000,
        ]);

        $saving = app(SavingService::class)->recordSaving(
            memberId: $member->id,
            savingTypeId: $savingType->id,
            amount: 50000,
            paymentMethod: 'cash',
            notes: 'Test setoran'
        );

        $journal = JournalEntry::where('reference_type', 'savings')->where('reference_id', $saving->id)->firstOrFail();

        $this->assertSame('simpan-pinjam', $journal->source_module);
        $this->assertSame('1001', $journal->debitAccount->code);
        $this->assertSame('2101', $journal->creditAccount->code);
        $this->assertSame($user->id, $journal->created_by);
        $this->assertSame($user->id, $journal->posted_by);
        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'ledger_scope' => 'savings',
            'transaction_type' => 'savings',
        ]);
    }

    public function test_successful_closing_records_closing_user(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $period = GlPeriod::where('status', 'open')->firstOrFail();

        $request = Request::create('/buku-besar-gl/tutup-buku', 'POST', [
            'period_id' => $period->id,
            'notes' => 'Closing period for test',
        ]);

        app(GeneralLedgerController::class)->processClosing($request);

        $this->assertSame('closed', $period->fresh()->status);
        $this->assertSame($user->id, $period->fresh()->closed_by);
    }

    public function test_loan_disbursement_creates_mapped_journal_and_ledger_entries(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = $this->createActiveMember();
        $loan = Loan::create([
            'application_number' => 'LOAN-2026-000001',
            'member_id' => $member->id,
            'loan_type' => 'serbaguna',
            'submission_date' => now()->toDateString(),
            'principal_amount' => 1000000,
            'interest_rate' => 12,
            'tenor_months' => 12,
            'repayment_method' => 'salary_cut',
            'purpose' => 'Kebutuhan operasional keluarga',
            'applicant_name' => $member->name,
            'applicant_nik' => $member->nik,
            'company_unit' => $member->company_unit,
            'applicant_phone' => $member->phone,
            'ktp_address' => $member->address,
            'current_address' => $member->address,
            'payroll_account_number' => $member->account_number,
            'approval_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'remaining_balance' => 1000000,
            'status' => 'approved',
            'application_status' => 'approved',
            'monthly_payment' => 100000,
        ]);

        app(LoanService::class)->disburse($loan->id);

        $journal = JournalEntry::where('reference_type', 'loan_disbursement')->where('reference_id', $loan->id)->firstOrFail();
        $this->assertSame('simpan-pinjam', $journal->source_module);
        $this->assertSame('1101', $journal->debitAccount->code);
        $this->assertSame('1001', $journal->creditAccount->code);
        $this->assertSame(2, $journal->generalLedgerEntries()->count());
        $this->assertSame('disbursed', $loan->fresh()->status);
        $this->assertSame(1000000.0, (float) $member->fresh()->balance_payable);
        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'ledger_scope' => 'loan_payable',
            'transaction_type' => 'loan_disbursement',
            'debit' => 1000000,
        ]);
    }

    public function test_loan_payment_creates_principal_and_interest_journals_and_completes_loan(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $member = $this->createActiveMember();
        $loan = Loan::create([
            'application_number' => 'LOAN-2026-000002',
            'member_id' => $member->id,
            'loan_type' => 'serbaguna',
            'submission_date' => now()->toDateString(),
            'principal_amount' => 800000,
            'interest_rate' => 12,
            'tenor_months' => 8,
            'repayment_method' => 'salary_cut',
            'purpose' => 'Pembiayaan kebutuhan mendesak',
            'applicant_name' => $member->name,
            'applicant_nik' => $member->nik,
            'company_unit' => $member->company_unit,
            'applicant_phone' => $member->phone,
            'ktp_address' => $member->address,
            'current_address' => $member->address,
            'payroll_account_number' => $member->account_number,
            'approval_date' => now()->toDateString(),
            'disbursement_date' => now()->toDateString(),
            'maturity_date' => now()->addMonths(8)->toDateString(),
            'remaining_balance' => 800000,
            'status' => 'active',
            'application_status' => 'disbursed',
            'monthly_payment' => 120000,
        ]);

        $payment = app(LoanService::class)->recordLoanPayment(
            loanId: $loan->id,
            principalPaid: 800000,
            interestPaid: 50000,
            paymentMethod: 'salary_cut',
            notes: 'Pelunasan pinjaman'
        );

        $journals = JournalEntry::where('reference_type', 'loan_payment')
            ->where('reference_id', $payment->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $journals);
        $this->assertSame(['1101', '4101'], $journals->pluck('creditAccount.code')->all());
        $this->assertTrue($journals->every(fn (JournalEntry $journal) => $journal->debitAccount->code === '1001'));
        $this->assertTrue($journals->every(fn (JournalEntry $journal) => $journal->generalLedgerEntries()->count() === 2));
        $this->assertSame('completed', $loan->fresh()->status);
        $this->assertSame('completed', $loan->fresh()->application_status);
        $this->assertSame(0.0, (float) $loan->fresh()->remaining_balance);
        $this->assertTrue((bool) LoanPayment::findOrFail($payment->id)->is_posted);
        $this->assertSame(0.0, (float) $member->fresh()->balance_payable);
        $this->assertDatabaseHas('member_ledgers', [
            'member_id' => $member->id,
            'ledger_scope' => 'loan_payable',
            'transaction_type' => 'loan_payment',
            'credit' => 800000,
        ]);
    }

    public function test_sync_legacy_data_assigns_periods_and_deactivates_unused_legacy_accounts(): void
    {
        $user = $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $legacyJournal = JournalEntry::create([
            'entry_date' => now()->toDateString(),
            'reference_type' => 'manual',
            'reference_id' => 0,
            'source_module' => 'manual',
            'reference_number' => null,
            'debit_account_id' => ChartOfAccount::where('code', '1102')->value('id'),
            'credit_account_id' => ChartOfAccount::where('code', '1001')->value('id'),
            'amount' => 1000,
            'memo' => 'Legacy journal without period',
            'status' => 'posted',
        ]);

        $response = app(GeneralLedgerController::class)->syncLegacyData();

        $this->assertNotNull($response);
        $this->assertNotNull($legacyJournal->fresh()->period_id);
        $this->assertStringStartsWith('LEGACY-JRN-', (string) $legacyJournal->fresh()->reference_number);
        $this->assertFalse((bool) ChartOfAccount::where('code', '4002')->value('is_active'));
        $this->assertFalse((bool) ChartOfAccount::where('code', '2102')->value('is_active'));
    }

    public function test_account_can_be_updated_into_sub_account_and_deactivated(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $parent = ChartOfAccount::where('code', '1502')->firstOrFail();
        $account = ChartOfAccount::where('code', '1002')->firstOrFail();

        $request = Request::create('/buku-besar-gl/daftar-akun-coa/' . $account->id, 'PUT', [
            'code' => '1502-01',
            'name' => 'Komputer Administrasi',
            'account_type' => 'asset',
            'account_group_id' => $parent->account_group_id,
            'normal_balance' => 'debit',
            'parent_id' => $parent->id,
            'is_header' => '0',
            'description' => 'Sub akun untuk komputer unit kantor.',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(GeneralLedgerController::class)->updateAccount($request, $account);

        $this->assertSame(route('gl.accounts'), $response->getTargetUrl());

        $updated = $account->fresh();
        $this->assertSame('1502-01', $updated->code);
        $this->assertSame('Komputer Administrasi', $updated->name);
        $this->assertSame($parent->id, $updated->parent_id);
        $this->assertFalse((bool) $updated->is_active);
    }

    public function test_account_cannot_use_its_descendant_as_parent(): void
    {
        $this->signInAsAdministrator();
        $this->seedGeneralLedgerDefaults();

        $parent = ChartOfAccount::create([
            'code' => '1502-H',
            'name' => 'Kelompok Peralatan',
            'account_type' => 'asset',
            'account_group_id' => ChartOfAccount::where('code', '1502')->value('account_group_id'),
            'normal_balance' => 'debit',
            'is_header' => true,
            'is_active' => true,
        ]);

        $child = ChartOfAccount::create([
            'code' => '1502-C',
            'name' => 'Sub Peralatan',
            'account_type' => 'asset',
            'account_group_id' => $parent->account_group_id,
            'normal_balance' => 'debit',
            'parent_id' => $parent->id,
            'is_header' => false,
            'is_active' => true,
        ]);

        $request = Request::create('/buku-besar-gl/daftar-akun-coa/' . $parent->id, 'PUT', [
            'code' => $parent->code,
            'name' => $parent->name,
            'account_type' => $parent->account_type,
            'account_group_id' => $parent->account_group_id,
            'normal_balance' => $parent->normal_balance,
            'parent_id' => $child->id,
            'is_header' => '1',
            'is_active' => '1',
            'description' => 'Header akun',
        ]);
        $request->headers->set('referer', route('gl.accounts'));
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());

        $response = app(GeneralLedgerController::class)->updateAccount($request, $parent);

        $this->assertSame(
            'Parent account tidak boleh berasal dari turunan akun ini.',
            $response->getSession()->get('errors')->first('parent_id')
        );
        $this->assertNull($parent->fresh()->parent_id);
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

    private function createActiveMember(): Member
    {
        static $counter = 1;

        $current = $counter++;

        return Member::create([
            'nik' => 'M-' . str_pad((string) $current, 3, '0', STR_PAD_LEFT),
            'name' => 'Anggota Test ' . $current,
            'email' => 'anggota' . $current . '@example.com',
            'phone' => '08123' . str_pad((string) $current, 6, '0', STR_PAD_LEFT),
            'address' => 'Jl. Test No. ' . $current,
            'company_unit' => 'Unit Test',
            'account_number' => 'ACC' . str_pad((string) $current, 6, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }
}
