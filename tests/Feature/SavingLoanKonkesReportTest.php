<?php

namespace Tests\Feature;

use App\Http\Controllers\SavingLoanController;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SavingLoanKonkesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_konkes_report_groups_loans_by_overdue_days(): void
    {
        $this->signInAsAdmin();

        $lancarMember = $this->createMember('M-001', 'Anggota Lancar');
        $dpkMember = $this->createMember('M-002', 'Anggota DPK');
        $macetMember = $this->createMember('M-003', 'Anggota Macet');

        $lancarLoan = $this->createLoan($lancarMember->id, [
            'application_number' => 'LOAN-001',
            'disbursement_date' => '2026-01-01',
            'approval_date' => '2025-12-28',
            'tenor_months' => 12,
            'monthly_payment' => 1000000,
            'remaining_balance' => 8000000,
        ]);
        $this->createPayment($lancarLoan->id, '2026-02-01', 1000000);
        $this->createPayment($lancarLoan->id, '2026-03-01', 1000000);

        $dpkLoan = $this->createLoan($dpkMember->id, [
            'application_number' => 'LOAN-002',
            'disbursement_date' => '2025-11-01',
            'approval_date' => '2025-10-28',
            'tenor_months' => 12,
            'monthly_payment' => 1000000,
            'remaining_balance' => 7000000,
        ]);
        $this->createPayment($dpkLoan->id, '2025-12-01', 1000000);
        $this->createPayment($dpkLoan->id, '2026-01-01', 1000000);

        $macetLoan = $this->createLoan($macetMember->id, [
            'application_number' => 'LOAN-003',
            'disbursement_date' => '2025-08-01',
            'approval_date' => '2025-07-28',
            'tenor_months' => 12,
            'monthly_payment' => 1000000,
            'remaining_balance' => 6000000,
        ]);
        $this->createPayment($macetLoan->id, '2025-09-01', 1000000);
        $this->createPayment($macetLoan->id, '2025-10-01', 1000000);
        $this->createPayment($macetLoan->id, '2025-11-01', 1000000);

        $request = Request::create('/simpan-pinjam/pinjaman/laporan-konkes', 'GET', [
            'report_date' => '2026-03-15',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => auth()->user());
        $route = app('router')->getRoutes()->getByName('simpan-pinjam.loans.konkes');
        $request->setRouteResolver(fn () => $route->bind($request));
        app()->instance('request', $request);

        $response = app(SavingLoanController::class)->loansKonkes($request);
        $summary = $response->getData()['konkesSummary'];
        $rows = collect($response->getData()['konkesLoans'])->keyBy(fn (array $row) => $row['loan']->application_number);

        $this->assertSame(1, $summary['lancar']['count']);
        $this->assertSame(1, $summary['dpk']['count']);
        $this->assertSame(1, $summary['macet']['count']);
        $this->assertSame('Lancar', $rows['LOAN-001']['quality']['label']);
        $this->assertSame('DPK', $rows['LOAN-002']['quality']['label']);
        $this->assertSame('Macet', $rows['LOAN-003']['quality']['label']);
        $this->assertEquals(42, $rows['LOAN-002']['days_overdue']);
        $this->assertEquals(104, $rows['LOAN-003']['days_overdue']);
    }

    private function signInAsAdmin(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withSession(['login_context' => 'pengurus'])->actingAs($user);

        return $user;
    }

    private function createMember(string $nik, string $name): Member
    {
        return Member::create([
            'nik' => $nik,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'status' => 'active',
        ]);
    }

    private function createLoan(int $memberId, array $attributes): Loan
    {
        return Loan::create(array_merge([
            'member_id' => $memberId,
            'loan_type' => 'serbaguna',
            'submission_date' => '2025-07-20',
            'principal_amount' => 12000000,
            'interest_rate' => 12,
            'tenor_months' => 12,
            'repayment_method' => 'salary_cut',
            'purpose' => 'Kebutuhan anggota',
            'applicant_name' => 'Test',
            'applicant_nik' => '123',
            'company_unit' => 'Unit A',
            'applicant_phone' => '08123456789',
            'ktp_address' => 'Alamat',
            'current_address' => 'Alamat',
            'payroll_account_number' => '00112233',
            'maturity_date' => '2026-12-31',
            'status' => 'active',
            'application_status' => 'disbursed',
            'notes' => null,
        ], $attributes));
    }

    private function createPayment(int $loanId, string $paymentDate, float $amount): LoanPayment
    {
        return LoanPayment::create([
            'loan_id' => $loanId,
            'payment_date' => $paymentDate,
            'principal_paid' => $amount,
            'interest_paid' => 0,
            'total_paid' => $amount,
            'payment_method' => 'salary_cut',
            'is_posted' => true,
        ]);
    }
}
