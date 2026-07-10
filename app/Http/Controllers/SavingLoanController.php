<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Loan;
use App\Models\LoanApproval;
use App\Models\LoanDocument;
use App\Models\LoanPayment;
use App\Models\Official;
use App\Models\Saving;
use App\Models\SavingType;
use App\Services\LoanService;
use App\Services\SavingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SavingLoanController extends Controller
{
    public function __construct(
        private SavingService $savingService,
        private LoanService $loanService
    ) {}

    public function index(): View
    {
        $defaultSection = $this->defaultLoanSection();
        $this->authorizeLoanSection($defaultSection);

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData($defaultSection)
        ));
    }

    public function loansMonitoring(): View
    {
        $this->authorizeLoanSection('monitoring');

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData('monitoring')
        ));
    }

    public function loansApplications(): View
    {
        $this->authorizeLoanSection('applications');

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData('applications')
        ));
    }

    public function loansApprovals(): View
    {
        $this->authorizeLoanSection('approvals');

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData('approvals')
        ));
    }

    public function loansDisbursement(): View
    {
        $this->authorizeLoanSection('disbursement');

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData('disbursement')
        ));
    }

    public function loansCompleted(): View
    {
        $this->authorizeLoanSection('completed');

        return view('saving-loan-loans', array_merge(
            $this->getSharedPageData(),
            $this->buildLoanSectionData('completed')
        ));
    }

    public function loansKonkes(Request $request): View
    {
        $this->authorizeLoanSection('konkes');

        $validated = $request->validate([
            'report_date' => ['nullable', 'date'],
        ]);

        $reportDate = isset($validated['report_date'])
            ? Carbon::parse($validated['report_date'])->startOfDay()
            : today()->startOfDay();

        return view('saving-loan-konkes', array_merge(
            $this->getSharedPageData(),
            $this->buildKonkesReportData($reportDate)
        ));
    }

    public function installments(): View
    {
        return view('saving-loan-installments', $this->getSharedPageData());
    }

    public function savings(): View
    {
        return view('saving-loan-savings', $this->getSharedPageData());
    }

    public function storeSaving(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'saving_type_id' => ['required', 'exists:saving_types,id'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'payment_method' => ['required', 'in:cash,transfer,salary_cut'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::findOrFail((int) $validated['member_id']);
        $this->ensureMemberCanTransact($member, 'setoran simpanan');

        $this->savingService->recordSaving(
            memberId: (int) $validated['member_id'],
            savingTypeId: (int) $validated['saving_type_id'],
            amount: (float) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? null,
        );

        return redirect()
            ->route('simpan-pinjam')
            ->with('success', 'Setoran simpanan berhasil dicatat.');
    }

    public function storeLoan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
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
        ]);

        $member = Member::findOrFail((int) $validated['member_id']);
        $this->ensureMemberCanTransact($member, 'pengajuan pinjaman');
        $this->validateLoanApplicationByType($validated);

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

        foreach ($this->defaultDocumentsForType($validated['loan_type']) as $document) {
            LoanDocument::create([
                'loan_id' => $loan->id,
                'document_type' => $document['type'],
                'document_label' => $document['label'],
                'status' => 'pending',
            ]);
        }

        $this->createApprovalChain($loan);

        return redirect()
            ->route('simpan-pinjam')
            ->with('success', "Pengajuan pinjaman {$loan->application_number} berhasil dibuat.");
    }

    public function processApproval(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000'],
        ]);

        $loan->loadMissing('approvals', 'member');
        $official = auth()->user()?->official?->loadMissing('position');

        abort_unless($official instanceof Official, 403, 'Akun ini belum terhubung ke pengurus koperasi.');

        $currentApproval = $this->resolveCurrentApproval($loan);
        abort_if(! $currentApproval, 422, 'Pinjaman ini tidak sedang menunggu approval.');

        $expectedCode = $this->approvalLevelToPositionCode((string) $currentApproval->approval_level);
        abort_if(($official->position?->code ?? null) !== $expectedCode, 403, 'Approval ini hanya bisa diproses oleh jabatan yang sesuai.');

        $currentApproval->update([
            'official_id' => $official->id,
            'status' => $validated['decision'],
            'action' => $validated['decision'],
            'notes' => $validated['notes'] ?? null,
            'acted_at' => now(),
        ]);

        if ($validated['decision'] === 'rejected') {
            $loan->approvals()
                ->where('id', '!=', $currentApproval->id)
                ->whereIn('status', ['pending', 'queued'])
                ->update([
                    'status' => 'skipped',
                    'action' => 'skipped',
                ]);

            $loan->update([
                'status' => 'rejected',
                'application_status' => 'rejected',
                'rejected_at' => now(),
                'approval_notes' => $validated['notes'] ?? null,
            ]);

            return redirect()
                ->route('simpan-pinjam.loans.approvals')
                ->with('success', "Pengajuan {$loan->application_number} ditolak oleh {$currentApproval->approval_level_label}.");
        }

        if ($currentApproval->approval_level === 'bendahara') {
            $loan->approvals()
                ->where('approval_level', 'ketua_koperasi')
                ->where('status', 'queued')
                ->update([
                    'status' => 'pending',
                    'action' => 'pending',
                ]);

            $loan->update([
                'application_status' => 'treasurer_approved',
                'verification_notes' => $validated['notes'] ?? null,
                'verified_at' => now(),
            ]);

            return redirect()
                ->route('simpan-pinjam.loans.approvals')
                ->with('success', "Pengajuan {$loan->application_number} disetujui Bendahara dan diteruskan ke Ketua Koperasi.");
        }

        $loan->update([
            'status' => 'approved',
            'approval_date' => now()->toDateString(),
            'application_status' => 'approved',
            'approved_at' => now(),
            'approval_notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route($this->currentUserIsAdmin() ? 'simpan-pinjam.loans.disbursement' : 'simpan-pinjam.loans.monitoring')
            ->with('success', "Pengajuan {$loan->application_number} disetujui Ketua Koperasi dan siap dicairkan.");
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'loan_id' => ['required', 'exists:loans,id'],
            'principal_paid' => ['required', 'numeric', 'min:0'],
            'interest_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,transfer,salary_cut'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $loan = Loan::with('member')->findOrFail((int) $validated['loan_id']);
        abort_unless(in_array($loan->status, ['disbursed', 'active'], true), 422, 'Pinjaman harus sudah dicairkan sebelum menerima pembayaran.');

        $this->loanService->recordLoanPayment(
            loanId: (int) $validated['loan_id'],
            principalPaid: (float) $validated['principal_paid'],
            interestPaid: (float) $validated['interest_paid'],
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? null,
        );

        return redirect()
            ->route('simpan-pinjam')
            ->with('success', 'Pembayaran pinjaman berhasil dicatat.');
    }

    public function processDisbursement(Loan $loan): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Pencairan hanya dapat dilakukan oleh admin.');
        abort_unless($loan->status === 'approved', 422, 'Pinjaman ini belum siap dicairkan.');

        $this->loanService->disburse($loan->id);

        return redirect()
            ->route('simpan-pinjam.loans.monitoring')
            ->with('success', "Pinjaman {$loan->application_number} berhasil dicairkan.");
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

    private function ensureMemberCanTransact(Member $member, string $context): void
    {
        if ($member->status !== 'active') {
            abort(422, "Anggota {$member->name} tidak bisa dipakai untuk {$context} karena statusnya " . ucfirst($member->status) . '.');
        }
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

    private function getSharedPageData(): array
    {
        $eligibleMembers = Member::where('status', 'active')->orderBy('name')->get();
        $activeLoans = Loan::with(['member', 'approvals.official.position'])
            ->whereIn('status', ['disbursed', 'active'])
            ->latest()
            ->take(20)
            ->get();
        $recentSavings = Saving::with(['member', 'savingType'])
            ->latest('deposit_date')
            ->take(12)
            ->get();
        $recentPayments = LoanPayment::with('loan.member')
            ->latest('payment_date')
            ->take(12)
            ->get();

        return [
            'members' => Member::orderBy('name')->get(),
            'eligibleMembers' => $eligibleMembers,
            'savingTypes' => SavingType::orderBy('name')->get(),
            'activeLoans' => $activeLoans,
            'recentSavings' => $recentSavings,
            'recentPayments' => $recentPayments,
            'savingLoanStats' => [
                'total_savings' => (float) Saving::sum('amount'),
                'active_loans' => (float) Loan::whereIn('status', ['disbursed', 'active'])->sum('remaining_balance'),
                'today_installments' => (float) LoanPayment::whereDate('payment_date', today())->sum('total_paid'),
                'active_members' => $eligibleMembers->count(),
            ],
            'recentActivities' => $this->buildRecentActivities($recentSavings, $recentPayments),
        ];
    }

    private function buildRecentActivities(Collection $recentSavings, Collection $recentPayments): Collection
    {
        $savings = $recentSavings->map(function (Saving $saving) {
            return [
                'id' => 'saving-' . $saving->id,
                'type' => 'Simpanan',
                'title' => $saving->savingType?->name ?? 'Setoran Simpanan',
                'member' => $saving->member?->name ?? '-',
                'date' => $saving->deposit_date ?? $saving->created_at,
                'amount' => (float) $saving->amount,
                'status' => $saving->is_posted ? 'Diposting' : 'Draft',
                'accent' => 'emerald',
                'icon' => 'fas fa-piggy-bank',
                'detail' => [
                    'label' => 'Setoran Simpanan',
                    'subtitle' => $saving->savingType?->name ?? 'Simpanan',
                    'member' => $saving->member?->name ?? '-',
                    'amount' => (float) $saving->amount,
                    'status' => $saving->is_posted ? 'Diposting' : 'Draft',
                    'date' => optional($saving->deposit_date ?? $saving->created_at)?->format('d M Y H:i'),
                    'method' => ucfirst(str_replace('_', ' ', (string) $saving->payment_method)),
                    'notes' => $saving->notes ?? '-',
                ],
            ];
        });

        $payments = $recentPayments->map(function (LoanPayment $payment) {
            return [
                'id' => 'payment-' . $payment->id,
                'type' => 'Pembayaran',
                'title' => 'Pembayaran Pinjaman #' . $payment->loan_id,
                'member' => $payment->loan?->member?->name ?? '-',
                'date' => $payment->payment_date ?? $payment->created_at,
                'amount' => (float) $payment->total_paid,
                'status' => $payment->is_posted ? 'Diposting' : 'Draft',
                'accent' => 'amber',
                'icon' => 'fas fa-money-check-dollar',
                'detail' => [
                    'label' => 'Pembayaran Pinjaman',
                    'subtitle' => 'Pinjaman #' . $payment->loan_id,
                    'member' => $payment->loan?->member?->name ?? '-',
                    'amount' => (float) $payment->total_paid,
                    'status' => $payment->is_posted ? 'Diposting' : 'Draft',
                    'date' => optional($payment->payment_date ?? $payment->created_at)?->format('d M Y H:i'),
                    'method' => ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
                    'notes' => $payment->notes ?? '-',
                ],
            ];
        });

        return $savings
            ->concat($payments)
            ->sortByDesc('date')
            ->take(12)
            ->values();
    }

    private function buildLoanSectionData(string $section): array
    {
        $routeName = request()->route()?->getName();
        $loanTabs = collect([
            ['key' => 'applications', 'label' => 'Pengajuan', 'route' => 'simpan-pinjam.loans.applications', 'active' => $routeName === 'simpan-pinjam.loans.applications'],
            ['key' => 'approvals', 'label' => 'Approval', 'route' => 'simpan-pinjam.loans.approvals', 'active' => $routeName === 'simpan-pinjam.loans.approvals'],
            ['key' => 'disbursement', 'label' => 'Pencairan', 'route' => 'simpan-pinjam.loans.disbursement', 'active' => $routeName === 'simpan-pinjam.loans.disbursement'],
            ['key' => 'monitoring', 'label' => 'Monitoring', 'route' => 'simpan-pinjam.loans.monitoring', 'active' => $routeName === 'simpan-pinjam.loans.monitoring'],
            ['key' => 'konkes', 'label' => 'Laporan KONKES', 'route' => 'simpan-pinjam.loans.konkes', 'active' => $routeName === 'simpan-pinjam.loans.konkes'],
            ['key' => 'completed', 'label' => 'Selesai', 'route' => 'simpan-pinjam.loans.completed', 'active' => $routeName === 'simpan-pinjam.loans.completed'],
        ])
            ->filter(fn (array $tab) => $this->canAccessLoanSection($tab['key']))
            ->values()
            ->all();

        $approvalBadge = $this->pendingApprovalCountForCurrentUser();
        $disbursementBadge = $this->pendingDisbursementCountForCurrentUser();

        $loanTabs = array_map(function (array $tab) use ($approvalBadge, $disbursementBadge) {
            if ($tab['key'] === 'approvals' && $approvalBadge > 0) {
                $tab['badge'] = $approvalBadge;
            }

            if ($tab['key'] === 'disbursement' && $disbursementBadge > 0) {
                $tab['badge'] = $disbursementBadge;
            }

            unset($tab['key']);

            return $tab;
        }, $loanTabs);

        $query = Loan::with(['member', 'approvals.official.position']);
        $title = 'Monitoring Pinjaman';
        $description = 'Pantau seluruh pipeline pinjaman dari pengajuan sampai pinjaman berjalan.';
        $emptyMessage = 'Belum ada data pinjaman pada tahap ini.';

        if ($section === 'applications') {
            $query->where('status', 'pending')->where('application_status', 'submitted');
            $title = 'Pengajuan Pinjaman';
            $description = 'Daftar pengajuan baru yang dibuat admin koperasi dan belum diproses ke tahap approval.';
            $emptyMessage = 'Belum ada pengajuan pinjaman baru.';
        } elseif ($section === 'approvals') {
            $query->where('status', 'pending')->whereIn('application_status', ['submitted', 'treasurer_approved']);

            $currentApprovalLevel = match ($this->currentUserApprovalPositionCode()) {
                'BENDAHARA' => 'bendahara',
                'KETUA_KOPERASI' => 'ketua_koperasi',
                default => null,
            };

            if ($currentApprovalLevel) {
                $query->whereHas('approvals', function ($approvalQuery) use ($currentApprovalLevel) {
                    $approvalQuery
                        ->where('approval_level', $currentApprovalLevel)
                        ->where('status', 'pending');
                });
            }

            $title = 'Approval Pinjaman';
            $description = 'Pantau pengajuan yang menunggu persetujuan Bendahara lalu Ketua Koperasi.';
            $emptyMessage = $currentApprovalLevel
                ? 'Tidak ada pinjaman yang perlu Anda setujui atau tolak saat ini.'
                : 'Tidak ada pinjaman yang menunggu approval.';
        } elseif ($section === 'disbursement') {
            $query->where('status', 'approved');
            $title = 'Pencairan Pinjaman';
            $description = 'Daftar pinjaman yang sudah siap diproses ke tahap pencairan.';
            $emptyMessage = 'Belum ada pinjaman yang siap dicairkan.';
        } elseif ($section === 'completed') {
            $query->where('status', 'completed');
            $title = 'Pinjaman Selesai';
            $description = 'Riwayat pinjaman yang sudah lunas dan selesai diproses.';
            $emptyMessage = 'Belum ada pinjaman yang selesai.';
        } else {
            $currentApprovalLevel = match ($this->currentUserApprovalPositionCode()) {
                'BENDAHARA' => 'bendahara',
                'KETUA_KOPERASI' => 'ketua_koperasi',
                default => null,
            };

            if ($currentApprovalLevel) {
                $query->where(function ($loanQuery) use ($currentApprovalLevel) {
                    $loanQuery
                        ->whereIn('status', ['approved', 'disbursed', 'active'])
                        ->orWhere(function ($pendingQuery) use ($currentApprovalLevel) {
                            $pendingQuery
                                ->where('status', 'pending')
                                ->whereHas('approvals', function ($approvalQuery) use ($currentApprovalLevel) {
                                    $approvalQuery
                                        ->where('approval_level', $currentApprovalLevel)
                                        ->where('status', 'pending');
                                });
                        });
                });
            } else {
                $query->whereIn('status', ['pending', 'approved', 'disbursed', 'active']);
            }
        }

        $loans = $query->latest()->take(20)->get();
        $currentOfficial = auth()->user()?->official?->loadMissing('position');

        $loans->each(function (Loan $loan) use ($currentOfficial) {
            $this->ensureApprovalChainExists($loan);
            $currentApproval = $this->resolveCurrentApproval($loan);

            $loan->setAttribute('current_approval_level_label', $currentApproval?->approval_level_label);
            $loan->setAttribute('current_approval_status_label', $currentApproval?->status_label);
            $loan->setAttribute('display_status_label', $this->resolveDisplayStatusLabel($loan, $currentApproval));
            $loan->setAttribute('display_status_class', $this->resolveDisplayStatusClass($loan, $currentApproval));
            $loan->setAttribute('progress_steps', $this->buildLoanProgressSteps($loan));
            $loan->setAttribute(
                'can_be_approved_by_current_user',
                $currentApproval
                && $currentOfficial
                && ($currentOfficial->position?->code === $this->approvalLevelToPositionCode((string) $currentApproval->approval_level))
            );
            $loan->setAttribute('can_be_disbursed', $loan->status === 'approved' && auth()->user()?->isAdmin());
        });

        return [
            'headerTabs' => $loanTabs,
            'loanSectionTitle' => $title,
            'loanSectionDescription' => $description,
            'loanSectionItems' => $loans,
            'loanSectionEmptyMessage' => $emptyMessage,
            'loanSectionActionable' => $section === 'approvals',
        ];
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

    private function ensureApprovalChainExists(Loan $loan): void
    {
        if (! $loan->relationLoaded('approvals')) {
            $loan->load('approvals');
        }

        if ($loan->approvals->isEmpty()) {
            $this->createApprovalChain($loan);
            $loan->load('approvals.official.position');
        }
    }

    private function resolveCurrentApproval(Loan $loan): ?LoanApproval
    {
        $this->ensureApprovalChainExists($loan);

        return $loan->approvals
            ->sortBy(fn (LoanApproval $approval) => $approval->approval_level === 'bendahara' ? 1 : 2)
            ->first(fn (LoanApproval $approval) => $approval->status === 'pending');
    }

    private function approvalLevelToPositionCode(string $approvalLevel): string
    {
        return match ($approvalLevel) {
            'bendahara' => 'BENDAHARA',
            default => 'KETUA_KOPERASI',
        };
    }

    private function authorizeLoanSection(string $section): void
    {
        abort_unless($this->canAccessLoanSection($section), 403, 'Anda tidak memiliki akses ke menu ini.');
    }

    private function canAccessLoanSection(string $section): bool
    {
        if ($this->currentUserIsAdmin()) {
            return in_array($section, ['disbursement', 'monitoring', 'konkes', 'completed'], true);
        }

        if ($this->currentUserIsAdministrator()) {
            return $section !== 'disbursement';
        }

        if ($this->currentUserCanApproveLoans()) {
            return in_array($section, ['approvals', 'monitoring', 'konkes'], true);
        }

        return in_array($section, ['monitoring', 'konkes'], true);
    }

    private function defaultLoanSection(): string
    {
        if ($this->currentUserIsAdmin()) {
            return 'monitoring';
        }

        if ($this->currentUserIsAdministrator()) {
            return 'approvals';
        }

        if ($this->currentUserCanApproveLoans()) {
            return 'approvals';
        }

        return 'monitoring';
    }

    private function currentUserIsAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    private function currentUserIsAdministrator(): bool
    {
        return (bool) auth()->user()?->isAdministrator();
    }

    private function currentUserCanApproveLoans(): bool
    {
        return in_array($this->currentUserApprovalPositionCode(), ['BENDAHARA', 'KETUA_KOPERASI'], true);
    }

    private function currentUserApprovalPositionCode(): ?string
    {
        return auth()->user()?->official?->loadMissing('position')->position?->code;
    }

    private function pendingApprovalCountForCurrentUser(): int
    {
        $approvalLevel = match ($this->currentUserApprovalPositionCode()) {
            'BENDAHARA' => 'bendahara',
            'KETUA_KOPERASI' => 'ketua_koperasi',
            default => null,
        };

        if (! $approvalLevel) {
            return 0;
        }

        return LoanApproval::query()
            ->where('approval_level', $approvalLevel)
            ->where('status', 'pending')
            ->count();
    }

    private function pendingDisbursementCountForCurrentUser(): int
    {
        if (! $this->currentUserIsAdmin()) {
            return 0;
        }

        return Loan::query()
            ->where('status', 'approved')
            ->count();
    }

    private function resolveDisplayStatusLabel(Loan $loan, ?LoanApproval $currentApproval): string
    {
        if ($loan->application_status === 'rejected' || $loan->status === 'rejected') {
            return 'Ditolak';
        }

        if ($loan->status === 'pending') {
            return match ($currentApproval?->approval_level) {
                'bendahara' => 'Menunggu Bendahara',
                'ketua_koperasi' => 'Menunggu Ketua Koperasi',
                default => 'Menunggu Proses',
            };
        }

        return $loan->status_label;
    }

    private function resolveDisplayStatusClass(Loan $loan, ?LoanApproval $currentApproval): string
    {
        if ($loan->application_status === 'rejected' || $loan->status === 'rejected') {
            return 'bg-rose-100 text-rose-700';
        }

        if ($loan->status === 'pending') {
            return match ($currentApproval?->approval_level) {
                'bendahara' => 'bg-amber-100 text-amber-700',
                'ketua_koperasi' => 'bg-sky-100 text-sky-700',
                default => 'bg-amber-100 text-amber-700',
            };
        }

        return match ($loan->status) {
            'approved', 'active', 'disbursed', 'completed' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }

    private function buildKonkesReportData(Carbon $reportDate): array
    {
        $routeName = request()->route()?->getName();
        $headerTabs = collect([
            ['label' => 'Pengajuan', 'route' => 'simpan-pinjam.loans.applications', 'active' => $routeName === 'simpan-pinjam.loans.applications'],
            ['label' => 'Approval', 'route' => 'simpan-pinjam.loans.approvals', 'active' => $routeName === 'simpan-pinjam.loans.approvals'],
            ['label' => 'Pencairan', 'route' => 'simpan-pinjam.loans.disbursement', 'active' => $routeName === 'simpan-pinjam.loans.disbursement'],
            ['label' => 'Monitoring', 'route' => 'simpan-pinjam.loans.monitoring', 'active' => $routeName === 'simpan-pinjam.loans.monitoring'],
            ['label' => 'Laporan KONKES', 'route' => 'simpan-pinjam.loans.konkes', 'active' => $routeName === 'simpan-pinjam.loans.konkes'],
            ['label' => 'Selesai', 'route' => 'simpan-pinjam.loans.completed', 'active' => $routeName === 'simpan-pinjam.loans.completed'],
        ])
            ->filter(fn (array $tab) => $this->canAccessLoanSection(match ($tab['route']) {
                'simpan-pinjam.loans.applications' => 'applications',
                'simpan-pinjam.loans.approvals' => 'approvals',
                'simpan-pinjam.loans.disbursement' => 'disbursement',
                'simpan-pinjam.loans.completed' => 'completed',
                'simpan-pinjam.loans.konkes' => 'konkes',
                default => 'monitoring',
            }))
            ->values()
            ->all();

        $loans = Loan::with(['member', 'payments'])
            ->whereIn('status', ['disbursed', 'active', 'completed'])
            ->orderByDesc('disbursement_date')
            ->get()
            ->map(function (Loan $loan) use ($reportDate) {
                return $this->buildKonkesLoanRow($loan, $reportDate);
            });

        $summary = $this->buildKonkesSummary($loans);

        return [
            'headerTabs' => $headerTabs,
            'reportDate' => $reportDate,
            'konkesLoans' => $loans,
            'konkesSummary' => $summary,
        ];
    }

    private function buildKonkesLoanRow(Loan $loan, Carbon $reportDate): array
    {
        $disbursementDate = ($loan->disbursement_date ?? $loan->approval_date ?? $loan->created_at)?->copy()->startOfDay();
        $payments = $loan->payments
            ->where('payment_date', '<=', $reportDate->toDateString())
            ->sortBy('payment_date')
            ->values();

        $totalPaid = (float) $payments->sum('total_paid');
        $installmentsDue = $disbursementDate ? $this->calculateInstallmentsDueCount($disbursementDate, $reportDate, (int) $loan->tenor_months) : 0;
        $scheduledAmount = $installmentsDue * (float) $loan->monthly_payment;
        $arrearsAmount = max(0, $scheduledAmount - $totalPaid);
        $oldestUnpaidDueDate = $arrearsAmount > 0 && $disbursementDate
            ? $this->resolveOldestUnpaidDueDate($disbursementDate, $reportDate, (int) $loan->tenor_months, (float) $loan->monthly_payment, $totalPaid)
            : null;
        $daysOverdue = $oldestUnpaidDueDate ? $oldestUnpaidDueDate->diffInDays($reportDate) : 0;
        $quality = $this->classifyKonkesQuality($daysOverdue);
        $installmentsInArrears = (float) $loan->monthly_payment > 0
            ? (int) ceil($arrearsAmount / max(1, (float) $loan->monthly_payment))
            : 0;

        return [
            'loan' => $loan,
            'member' => $loan->member,
            'disbursement_date' => $disbursementDate,
            'installments_due' => $installmentsDue,
            'scheduled_amount' => $scheduledAmount,
            'total_paid' => $totalPaid,
            'arrears_amount' => $arrearsAmount,
            'days_overdue' => $daysOverdue,
            'installments_in_arrears' => $installmentsInArrears,
            'oldest_unpaid_due_date' => $oldestUnpaidDueDate,
            'quality' => $quality,
        ];
    }

    private function calculateInstallmentsDueCount(Carbon $disbursementDate, Carbon $reportDate, int $tenorMonths): int
    {
        $count = 0;

        for ($month = 1; $month <= $tenorMonths; $month++) {
            $dueDate = $disbursementDate->copy()->addMonthsNoOverflow($month);

            if ($dueDate->lte($reportDate)) {
                $count++;
            }
        }

        return $count;
    }

    private function resolveOldestUnpaidDueDate(
        Carbon $disbursementDate,
        Carbon $reportDate,
        int $tenorMonths,
        float $monthlyPayment,
        float $totalPaid
    ): ?Carbon {
        if ($monthlyPayment <= 0) {
            return null;
        }

        for ($month = 1; $month <= $tenorMonths; $month++) {
            $dueDate = $disbursementDate->copy()->addMonthsNoOverflow($month);

            if ($dueDate->gt($reportDate)) {
                break;
            }

            $scheduledUpToDueDate = $month * $monthlyPayment;

            if ($totalPaid + 0.01 < $scheduledUpToDueDate) {
                return $dueDate;
            }
        }

        return null;
    }

    private function classifyKonkesQuality(int $daysOverdue): array
    {
        if ($daysOverdue > 90) {
            return [
                'code' => 'macet',
                'label' => 'Macet',
                'class' => 'bg-rose-100 text-rose-700',
                'description' => 'Tunggakan lebih dari 90 hari.',
            ];
        }

        if ($daysOverdue > 30) {
            return [
                'code' => 'dpk',
                'label' => 'DPK',
                'class' => 'bg-amber-100 text-amber-700',
                'description' => 'Tunggakan 31 sampai 90 hari.',
            ];
        }

        return [
            'code' => 'lancar',
            'label' => 'Lancar',
            'class' => 'bg-emerald-100 text-emerald-700',
            'description' => 'Tunggakan sampai 30 hari.',
        ];
    }

    private function buildKonkesSummary(Collection $loans): array
    {
        $totalOutstanding = (float) $loans->sum(fn (array $row) => (float) $row['loan']->remaining_balance);
        $totalArrears = (float) $loans->sum('arrears_amount');

        $groups = collect(['lancar', 'dpk', 'macet'])->mapWithKeys(function (string $code) use ($loans, $totalOutstanding) {
            $items = $loans->filter(fn (array $row) => $row['quality']['code'] === $code);
            $outstanding = (float) $items->sum(fn (array $row) => (float) $row['loan']->remaining_balance);

            return [
                $code => [
                    'count' => $items->count(),
                    'outstanding' => $outstanding,
                    'ratio' => $totalOutstanding > 0 ? ($outstanding / $totalOutstanding) * 100 : 0,
                ],
            ];
        })->all();

        return [
            'total_loans' => $loans->count(),
            'total_outstanding' => $totalOutstanding,
            'total_arrears' => $totalArrears,
            'lancar' => $groups['lancar'],
            'dpk' => $groups['dpk'],
            'macet' => $groups['macet'],
        ];
    }

    private function buildLoanProgressSteps(Loan $loan): array
    {
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
