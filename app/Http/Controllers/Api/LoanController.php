<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanApproval;
use App\Models\LoanDocument;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Services\LoanService;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService) {}

    private function canManageLoanFor(int $memberId): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdministrator() || $user?->isAdmin() || (int) ($user?->member_id ?? 0) === $memberId);
    }

    /**
     * Buat permohonan pinjaman baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'loan_type' => 'required|in:emergency,pendidikan,serbaguna,multiguna_plus',
            'principal_amount' => 'required|numeric|min:100000',
            'interest_rate' => 'required|numeric|min:0.5|max:20',
            'tenor_months' => 'required|integer|min:1|max:60',
            'repayment_method' => 'required|in:salary_cut,payroll_debit,transfer',
            'purpose' => 'required|string|max:2000',
            'family_member_name' => 'nullable|string|max:255',
            'family_relationship' => 'nullable|string|max:255',
            'child_number' => 'nullable|integer|min:1|max:20',
            'education_level' => 'nullable|string|max:255',
            'school_name' => 'nullable|string|max:255',
            'school_address' => 'nullable|string|max:1000',
            'school_phone' => 'nullable|string|max:50',
            'hospital_name' => 'nullable|string|max:255',
            'hospital_address' => 'nullable|string|max:1000',
            'hospital_phone' => 'nullable|string|max:50',
            'collateral_type' => 'nullable|string|max:255',
            'collateral_value' => 'nullable|numeric|min:0',
            'collateral_description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:255',
        ]);

        abort_unless($this->canManageLoanFor((int) $validated['member_id']), 403, 'Anda tidak dapat membuat pinjaman untuk anggota ini.');

        $member = Member::findOrFail((int) $validated['member_id']);
        if ($member->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => "Anggota {$member->name} tidak bisa mengajukan pinjaman karena statusnya " . ucfirst($member->status) . '.',
            ], 422);
        }

        try {
            $this->validateLoanApplicationByType($validated);
            $maturityDate = now()->addMonths($validated['tenor_months']);
            $monthlyPayment = $this->calculateMonthlyPayment(
                $validated['principal_amount'],
                $validated['interest_rate'],
                $validated['tenor_months']
            );

            $loan = Loan::create([
                'application_number' => $this->generateApplicationNumber(),
                'member_id' => $validated['member_id'],
                'loan_type' => $validated['loan_type'],
                'submission_date' => now()->toDateString(),
                'principal_amount' => $validated['principal_amount'],
                'interest_rate' => $validated['interest_rate'],
                'tenor_months' => $validated['tenor_months'],
                'repayment_method' => $validated['repayment_method'],
                'purpose' => $validated['purpose'],
                'applicant_name' => $member->name,
                'applicant_nik' => $member->nik,
                'company_unit' => $member->company_unit,
                'applicant_phone' => $member->phone,
                'ktp_address' => $member->address,
                'current_address' => $member->address,
                'payroll_account_number' => $member->account_number,
                'family_member_name' => $validated['family_member_name'] ?? null,
                'family_relationship' => $validated['family_relationship'] ?? null,
                'child_number' => $validated['child_number'] ?? null,
                'education_level' => $validated['education_level'] ?? null,
                'school_name' => $validated['school_name'] ?? null,
                'school_address' => $validated['school_address'] ?? null,
                'school_phone' => $validated['school_phone'] ?? null,
                'hospital_name' => $validated['hospital_name'] ?? null,
                'hospital_address' => $validated['hospital_address'] ?? null,
                'hospital_phone' => $validated['hospital_phone'] ?? null,
                'collateral_type' => $validated['collateral_type'] ?? null,
                'collateral_value' => $validated['collateral_value'] ?? null,
                'collateral_description' => $validated['collateral_description'] ?? null,
                'approval_date' => null,
                'disbursement_date' => null,
                'maturity_date' => $maturityDate,
                'remaining_balance' => $validated['principal_amount'],
                'monthly_payment' => $monthlyPayment,
                'status' => 'pending',
                'application_status' => 'submitted',
                'notes' => $validated['notes'] ?? null,
                'member_notes' => $validated['notes'] ?? null,
            ]);

            foreach ($this->defaultDocumentsForType($validated['loan_type']) as $document) {
                LoanDocument::create([
                    'loan_id' => $loan->id,
                    'document_type' => $document['type'],
                    'document_label' => $document['label'],
                    'status' => 'pending',
                ]);
            }

            $this->createApprovalChain($loan);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan pinjaman berhasil dibuat',
                'data' => $loan->load('documents'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat permohonan pinjaman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve pinjaman
     */
    public function approve(Loan $loan)
    {
        if ($loan->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pinjaman harus dalam status pending untuk disetujui',
            ], 400);
        }

        try {
            $loan->loadMissing('approvals');
            $this->ensureApprovalChain($loan);
            $approval = $loan->approvals
                ->sortBy(fn (LoanApproval $item) => $item->approval_level === 'bendahara' ? 1 : 2)
                ->first(fn (LoanApproval $item) => $item->status === 'pending');

            if (! $approval) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada tahap approval yang sedang aktif.',
                ], 422);
            }

            $official = auth()->user()?->official?->loadMissing('position');
            $requiredCode = $approval->approval_level === 'bendahara' ? 'BENDAHARA' : 'KETUA_KOPERASI';

            if (($official->position?->code ?? null) !== $requiredCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Approval ini hanya bisa diproses oleh jabatan yang sesuai.',
                ], 403);
            }

            $approval->update([
                'official_id' => $official->id,
                'status' => 'approved',
                'action' => 'approved',
                'notes' => 'Disetujui melalui workflow approval pinjaman.',
                'acted_at' => now(),
            ]);

            if ($approval->approval_level === 'bendahara') {
                $loan->approvals()
                    ->where('approval_level', 'ketua_koperasi')
                    ->where('status', 'queued')
                    ->update([
                        'status' => 'pending',
                        'action' => 'pending',
                    ]);

                $loan->update([
                    'application_status' => 'treasurer_approved',
                    'verified_at' => now(),
                ]);
            } else {
                $loan->update([
                    'status' => 'approved',
                    'approval_date' => now(),
                    'application_status' => 'approved',
                    'approved_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pinjaman berhasil disetujui',
                'data' => $loan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui pinjaman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pencairan pinjaman
     */
    public function disburse(Loan $loan)
    {
        if (! auth()->user()?->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Pencairan hanya dapat dilakukan oleh admin.',
            ], 403);
        }

        if ($loan->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Pinjaman harus sudah lolos approval sebelum dicairkan.',
            ], 422);
        }

        try {
            $loan = $this->loanService->disburse($loan->id);

            return response()->json([
                'success' => true,
                'message' => 'Pinjaman berhasil dicairkan',
                'data' => $loan,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencairkan pinjaman: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Catat pembayaran pinjaman
     */
    public function payment(Request $request, Loan $loan)
    {
        abort_unless($this->canManageLoanFor((int) $loan->member_id), 403, 'Anda tidak dapat mencatat pembayaran pinjaman ini.');

        $validated = $request->validate([
            'principal_paid' => 'required|numeric|min:0',
            'interest_paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,salary_cut',
            'notes' => 'nullable|string',
        ]);

        if (! in_array($loan->status, ['disbursed', 'active'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Pinjaman harus sudah dicairkan sebelum menerima pembayaran.',
            ], 422);
        }

        try {
            $payment = $this->loanService->recordLoanPayment(
                loanId: $loan->id,
                principalPaid: $validated['principal_paid'],
                interestPaid: $validated['interest_paid'],
                paymentMethod: $validated['payment_method'] ?? 'salary_cut',
                notes: $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran pinjaman berhasil dicatat',
                'data' => $payment->load('loan'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lihat riwayat pembayaran
     */
    public function paymentHistory(Loan $loan)
    {
        abort_unless($this->canManageLoanFor((int) $loan->member_id), 403, 'Anda tidak dapat melihat riwayat pinjaman ini.');

        $payments = LoanPayment::where('loan_id', $loan->id)
            ->latest('payment_date')
            ->paginate();

        return response()->json([
            'success' => true,
            'loan' => $loan,
            'data' => $payments,
        ]);
    }

    /**
     * Hitung monthly payment menggunakan formula bunga
     */
    private function calculateMonthlyPayment(float $principal, float $monthlyRate, int $months): float
    {
        $monthlyRateDecimal = $monthlyRate / 100 / 12;
        if ($monthlyRateDecimal == 0) {
            return $principal / $months;
        }

        return $principal * ($monthlyRateDecimal * pow(1 + $monthlyRateDecimal, $months)) /
            (pow(1 + $monthlyRateDecimal, $months) - 1);
    }

    private function generateApplicationNumber(): string
    {
        $nextId = ((int) Loan::max('id')) + 1;

        return 'LOAN-' . now()->format('Y') . '-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    private function validateLoanApplicationByType(array $validated): void
    {
        if ($validated['loan_type'] === 'emergency') {
            validator($validated, [
                'family_member_name' => ['required', 'string', 'max:255'],
                'family_relationship' => ['required', 'string', 'max:255'],
                'hospital_name' => ['required', 'string', 'max:255'],
            ])->validate();
        }

        if ($validated['loan_type'] === 'pendidikan') {
            validator($validated, [
                'family_member_name' => ['required', 'string', 'max:255'],
                'family_relationship' => ['required', 'string', 'max:255'],
                'education_level' => ['required', 'string', 'max:255'],
                'school_name' => ['required', 'string', 'max:255'],
            ])->validate();
        }

        if ($validated['loan_type'] === 'multiguna_plus') {
            validator($validated, [
                'collateral_type' => ['required', 'string', 'max:255'],
            ])->validate();
        }
    }

    private function defaultDocumentsForType(string $loanType): array
    {
        return match ($loanType) {
            'emergency' => [
                ['type' => 'hospital_receipt', 'label' => 'Kwitansi / Estimasi Rumah Sakit'],
                ['type' => 'spouse_identity', 'label' => 'Fotokopi KTP Suami / Istri'],
            ],
            'pendidikan' => [
                ['type' => 'family_card', 'label' => 'Kartu Keluarga / Akte Lahir'],
                ['type' => 'school_bill', 'label' => 'Bukti Biaya Pendidikan'],
            ],
            'multiguna_plus' => [
                ['type' => 'collateral_document', 'label' => 'Dokumen Jaminan'],
                ['type' => 'identity_document', 'label' => 'Dokumen Identitas Pendukung'],
            ],
            default => [
                ['type' => 'identity_document', 'label' => 'Dokumen Identitas Pendukung'],
            ],
        };
    }

    private function createApprovalChain(Loan $loan): void
    {
        if ($loan->approvals()->exists()) {
            return;
        }

        LoanApproval::create([
            'loan_id' => $loan->id,
            'approval_level' => 'bendahara',
            'status' => 'pending',
            'action' => 'pending',
            'acted_at' => now(),
        ]);

        LoanApproval::create([
            'loan_id' => $loan->id,
            'approval_level' => 'ketua_koperasi',
            'status' => 'queued',
            'action' => 'queued',
            'acted_at' => now(),
        ]);
    }

    private function ensureApprovalChain(Loan $loan): void
    {
        if ($loan->approvals->isEmpty()) {
            $this->createApprovalChain($loan);
            $loan->load('approvals');
        }
    }
}
