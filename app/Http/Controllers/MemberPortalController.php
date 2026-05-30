<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanApproval;
use App\Models\LoanDocument;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\Saving;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberPortalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user()->loadMissing('member', 'official.member');
        $member = $user->member ?? $user->official?->member;

        abort_unless($member instanceof Member, 403, 'Akun ini belum terhubung ke data anggota.');

        $member->load([
            'savings.savingType',
            'loans' => fn ($query) => $query->with(['documents', 'approvals'])->latest(),
        ]);

        $recentPayments = LoanPayment::with('loan')
            ->whereHas('loan', fn ($query) => $query->where('member_id', $member->id))
            ->latest('payment_date')
            ->take(5)
            ->get();

        $savingsSummary = $member->savings
            ->groupBy(fn (Saving $saving) => $saving->savingType?->name ?? 'Lainnya')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'amount' => (float) $items->sum('amount'),
                'description' => match (strtolower((string) $name)) {
                    'simpanan pokok' => 'Simpanan dasar keanggotaan',
                    'simpanan wajib' => 'Setoran rutin anggota',
                    'simpanan sukarela' => 'Tabungan fleksibel anggota',
                    default => 'Akumulasi transaksi simpanan',
                },
            ])
            ->values();

        $loanHistoryItems = $member->loans
            ->map(function (Loan $loan) {
                $loan->setAttribute('progress_steps', $this->buildLoanProgressSteps($loan));

                return $loan;
            });

        return view('member-portal', [
            'member' => $member,
            'memberPortalStats' => [
                'total_savings' => (float) $member->savings->sum('amount'),
                'outstanding_loans' => (float) $member->loans
                    ->whereIn('status', ['approved', 'disbursed', 'active'])
                    ->sum('remaining_balance'),
                'active_loan_count' => (int) $member->loans
                    ->whereIn('status', ['approved', 'disbursed', 'active'])
                    ->count(),
            ],
            'savingsSummary' => $savingsSummary,
            'recentActivities' => $this->buildRecentActivities($member, $recentPayments),
            'loanHistoryItems' => $loanHistoryItems,
        ]);
    }

    public function createLoan(): View
    {
        $member = $this->resolveMember();

        return view('member-loan-apply', [
            'member' => $member,
            'latestLoan' => $member->loans()->latest()->first(),
            'documentRequirements' => $this->documentRequirements(),
        ]);
    }

    public function storeLoan(Request $request): RedirectResponse
    {
        $member = $this->resolveMember();

        abort_if($member->status !== 'active', 422, 'Status anggota tidak aktif untuk pengajuan pinjaman.');

        $validated = $request->validate([
            'loan_type' => ['required', 'in:emergency,pendidikan,serbaguna,multiguna_plus'],
            'principal_amount' => ['required', 'numeric', 'min:100000'],
            'interest_rate' => ['required', 'numeric', 'min:0.5', 'max:20'],
            'tenor_months' => ['required', 'integer', 'min:1', 'max:60'],
            'repayment_method' => ['required', 'in:salary_cut,payroll_debit,transfer'],
            'purpose' => ['required', 'string', 'max:2000'],
            'family_member_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string', 'max:255'],
            'child_number' => ['nullable', 'integer', 'min:1', 'max:20'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:1000'],
            'school_phone' => ['nullable', 'string', 'max:50'],
            'hospital_name' => ['nullable', 'string', 'max:255'],
            'hospital_address' => ['nullable', 'string', 'max:1000'],
            'hospital_phone' => ['nullable', 'string', 'max:50'],
            'collateral_type' => ['nullable', 'string', 'max:255'],
            'collateral_value' => ['nullable', 'numeric', 'min:0'],
            'collateral_description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ] + $this->documentValidationRules((string) $request->input('loan_type')));

        $this->validateLoanApplicationByType($validated);

        $loan = Loan::create([
            'application_number' => $this->generateApplicationNumber(),
            'member_id' => $member->id,
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
            'maturity_date' => now()->addMonths((int) $validated['tenor_months']),
            'remaining_balance' => $validated['principal_amount'],
            'monthly_payment' => $this->calculateMonthlyPayment(
                (float) $validated['principal_amount'],
                (float) $validated['interest_rate'],
                (int) $validated['tenor_months']
            ),
            'status' => 'pending',
            'application_status' => 'submitted',
            'notes' => $validated['notes'] ?? null,
            'member_notes' => $validated['notes'] ?? null,
        ]);

        $this->storeLoanDocuments($loan, $request, $validated['loan_type']);

        $this->createApprovalChain($loan);

        return redirect()
            ->route('member-portal')
            ->with('success', "Pengajuan pinjaman {$loan->application_number} berhasil dikirim.");
    }

    public function loans(): View
    {
        $member = $this->resolveMember();
        $loans = $member->loans()
            ->with(['documents', 'approvals'])
            ->latest('submission_date')
            ->latest('created_at')
            ->get();

        $loans->each(function (Loan $loan) {
            $loan->setAttribute('progress_steps', $this->buildLoanProgressSteps($loan));
        });

        return view('member-loan-status', [
            'member' => $member,
            'loans' => $loans,
        ]);
    }

    public function downloadDocument(LoanDocument $document): BinaryFileResponse
    {
        $member = $this->resolveMember();
        $document->loadMissing('loan');

        abort_unless((int) $document->loan?->member_id === (int) $member->id, 403);
        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        $fileName = $document->original_name ?: basename($document->file_path);
        $absolutePath = Storage::disk('local')->path($document->file_path);

        return response()->file(
            $absolutePath,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            ]
        );
    }

    private function buildRecentActivities(Member $member, Collection $recentPayments): Collection
    {
        $savingActivities = $member->savings
            ->sortByDesc('deposit_date')
            ->take(5)
            ->map(function (Saving $saving) {
                return [
                    'title' => 'Setoran ' . ($saving->savingType?->name ?? 'Simpanan'),
                    'date' => $saving->deposit_date ?? $saving->created_at,
                    'amount' => (float) $saving->amount,
                    'direction' => 'in',
                    'detail' => ucfirst((string) $saving->payment_method),
                    'icon' => 'fas fa-arrow-down',
                    'accent' => 'emerald',
                ];
            });

        $paymentActivities = $recentPayments->map(function (LoanPayment $payment) {
            return [
                'title' => 'Pembayaran Pinjaman',
                'date' => $payment->payment_date ?? $payment->created_at,
                'amount' => (float) $payment->total_paid,
                'direction' => 'out',
                'detail' => 'Pokok Rp ' . number_format((float) $payment->principal_paid, 0, ',', '.') .
                    ' + Bunga Rp ' . number_format((float) $payment->interest_paid, 0, ',', '.'),
                'icon' => 'fas fa-hand-holding-dollar',
                'accent' => 'rose',
            ];
        });

        $loanActivities = $member->loans
            ->sortByDesc('submission_date')
            ->take(5)
            ->map(function ($loan) {
                return [
                    'title' => 'Pengajuan Pinjaman ' . $loan->loan_type_label,
                    'date' => $loan->submission_date ?? $loan->created_at,
                    'amount' => (float) $loan->principal_amount,
                    'direction' => 'loan',
                    'detail' => $loan->application_status_label,
                    'icon' => 'fas fa-file-signature',
                    'accent' => $loan->application_status === 'rejected' ? 'rose' : 'sky',
                    'notes' => $loan->application_status === 'rejected' ? ($loan->approval_notes ?? 'Pengajuan ditolak.') : null,
                ];
            });

        return $savingActivities
            ->concat($paymentActivities)
            ->concat($loanActivities)
            ->sortByDesc('date')
            ->take(10)
            ->values();
    }

    private function resolveMember(): Member
    {
        $user = auth()->user()->loadMissing('member', 'official.member');
        $member = $user->member ?? $user->official?->member;

        abort_unless($member instanceof Member, 403, 'Akun ini belum terhubung ke data anggota.');

        return $member;
    }

    private function generateApplicationNumber(): string
    {
        $nextId = ((int) Loan::max('id')) + 1;

        return 'LOAN-' . now()->format('Y') . '-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    private function calculateMonthlyPayment(float $principal, float $interestRate, int $months): float
    {
        $monthlyRateDecimal = $interestRate / 100 / 12;

        if ($monthlyRateDecimal == 0.0) {
            return $principal / $months;
        }

        return $principal * ($monthlyRateDecimal * pow(1 + $monthlyRateDecimal, $months)) /
            (pow(1 + $monthlyRateDecimal, $months) - 1);
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

    private function documentRequirements(): array
    {
        return [
            'emergency' => $this->defaultDocumentsForType('emergency'),
            'pendidikan' => $this->defaultDocumentsForType('pendidikan'),
            'serbaguna' => $this->defaultDocumentsForType('serbaguna'),
            'multiguna_plus' => $this->defaultDocumentsForType('multiguna_plus'),
        ];
    }

    private function documentValidationRules(string $loanType): array
    {
        $rules = [];

        foreach ($this->defaultDocumentsForType($loanType) as $document) {
            $rules["documents.{$document['type']}"] = ['required', 'file', 'mimes:pdf', 'max:5120'];
        }

        return $rules;
    }

    private function storeLoanDocuments(Loan $loan, Request $request, string $loanType): void
    {
        foreach ($this->defaultDocumentsForType($loanType) as $document) {
            $file = $request->file("documents.{$document['type']}");
            $filePath = null;
            $originalName = null;
            $status = 'pending';

            if ($file) {
                $originalName = $file->getClientOriginalName();
                $filePath = $file->storeAs(
                    'loan-documents/' . $loan->application_number,
                    $document['type'] . '-' . now()->format('YmdHis') . '.pdf'
                );
                $status = 'uploaded';
            }

            LoanDocument::create([
                'loan_id' => $loan->id,
                'document_type' => $document['type'],
                'document_label' => $document['label'],
                'status' => $status,
                'file_path' => $filePath,
                'original_name' => $originalName,
            ]);
        }
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

    private function buildLoanProgressSteps(Loan $loan): array
    {
        $rejected = $loan->application_status === 'rejected' || $loan->status === 'rejected';

        if ($rejected) {
            return [
                ['label' => 'Diajukan', 'done' => false],
                ['label' => 'Disetujui Bendahara', 'done' => false],
                ['label' => 'Disetujui Ketua', 'done' => false],
                ['label' => 'Dicairkan', 'done' => false],
                ['label' => 'Selesai', 'done' => false],
                ['label' => 'Ditolak', 'done' => true, 'rejected' => true],
            ];
        }

        $submitted = true;
        $treasurerApproved = in_array($loan->application_status, ['treasurer_approved', 'approved', 'disbursed', 'completed'], true);
        $chairmanApproved = in_array($loan->application_status, ['approved', 'disbursed', 'completed'], true);
        $disbursed = in_array($loan->application_status, ['disbursed', 'completed'], true);
        $completed = $loan->application_status === 'completed' || $loan->status === 'completed';

        return [
            ['label' => 'Diajukan', 'done' => $submitted],
            ['label' => 'Disetujui Bendahara', 'done' => $treasurerApproved],
            ['label' => 'Disetujui Ketua', 'done' => $chairmanApproved],
            ['label' => 'Dicairkan', 'done' => $disbursed],
            ['label' => 'Selesai', 'done' => $completed],
        ];
    }
}
