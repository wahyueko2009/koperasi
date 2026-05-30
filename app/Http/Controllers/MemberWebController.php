<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\MemberReceivablePayment;
use App\Models\RetailTransaction;
use App\Models\Saving;
use App\Models\User;
use App\Services\MemberReceivableService;
use OpenSpout\Common\Entity\Row;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberWebController extends Controller
{
    public function __construct(private MemberReceivableService $memberReceivableService) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $members = $this->memberQuery($request)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $selectedMemberId = $request->integer('selected');
        $selectedMember = null;

        if ($selectedMemberId > 0) {
            $selectedMember = $this->loadMemberDetail($selectedMemberId);
        }

        $memberDetails = $members->getCollection()
            ->mapWithKeys(fn (Member $member) => [$member->id => $this->loadMemberDetail((int) $member->id)])
            ->filter();

        if ($selectedMember && !$memberDetails->has($selectedMember->id)) {
            $memberDetails->put($selectedMember->id, $selectedMember);
        }

        return view('members', [
            'members' => $members,
            'selectedMember' => $selectedMember,
            'memberDetails' => $memberDetails,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'memberStats' => [
                'total' => Member::count(),
                'active' => Member::where('status', 'active')->count(),
                'inactive' => Member::where('status', 'inactive')->count(),
                'suspended' => Member::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('members-form', [
            'member' => new Member(),
            'pageTitle' => 'Tambah Anggota',
            'pageDescription' => 'Tambahkan anggota baru sebagai fondasi transaksi koperasi.',
            'formAction' => route('members.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $members = $this->memberQuery($request)
            ->orderBy('name')
            ->get();

        $fileName = 'anggota-koperasi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'NIK',
                'Nama Lengkap',
                'Email',
                'Nama Pasangan',
                'NPWP',
                'Telepon',
                'Alamat',
                'Company/Unit Kerja',
                'No Rekening',
                'Status',
                'Piutang Retail',
                'Hutang Anggota',
                'Tanggal Terdaftar',
            ]);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->nik,
                    $member->name,
                    $member->email,
                    $member->spouse_name,
                    $member->npwp,
                    $member->phone,
                    $member->address,
                    $member->company_unit,
                    $member->account_number,
                    ucfirst($member->status),
                    (float) $member->balance_receivable,
                    (float) $member->balance_payable,
                    $member->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $members = $this->memberQuery($request)
            ->orderBy('name')
            ->get();

        $directory = storage_path('app/exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = 'anggota-koperasi-' . now()->format('Ymd-His') . '.xlsx';
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        $writer = new XlsxWriter();
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues([
            'NIK',
            'Nama Lengkap',
            'Email',
            'Nama Pasangan',
            'NPWP',
            'Telepon',
            'Alamat',
            'Company/Unit Kerja',
            'No Rekening',
            'Status',
            'Piutang Retail',
            'Hutang Anggota',
            'Tanggal Terdaftar',
        ]));

        foreach ($members as $member) {
            $writer->addRow(Row::fromValues([
                $member->nik,
                $member->name,
                $member->email,
                $member->spouse_name ?? '',
                $member->npwp ?? '',
                $member->phone ?? '',
                $member->address ?? '',
                $member->company_unit ?? '',
                $member->account_number ?? '',
                ucfirst($member->status),
                (float) $member->balance_receivable,
                (float) $member->balance_payable,
                $member->created_at?->format('Y-m-d H:i:s') ?? '',
            ]));
        }

        $writer->close();

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMember($request);
        $member = Member::create($this->extractMemberPayload($validated));
        $this->syncMemberUser($member, $validated);

        return redirect()
            ->route('members', ['selected' => $member->id])
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Member $member): View
    {
        return view('members-form', [
            'member' => $member,
            'pageTitle' => 'Edit Anggota',
            'pageDescription' => 'Perbarui data anggota agar tetap rapi dan siap dipakai modul lain.',
            'formAction' => route('members.update', $member),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $this->validateMember($request, $member);
        $member->update($this->extractMemberPayload($validated));
        $this->syncMemberUser($member, $validated);

        return redirect()
            ->route('members', ['selected' => $member->id])
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function storeReceivablePayment(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->memberReceivableService->recordPayment(
            memberId: $member->id,
            amount: (float) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? null,
            paymentDate: $validated['payment_date'],
        );

        return redirect()
            ->route('members', ['selected' => $member->id])
            ->with('success', 'Pelunasan piutang retail anggota berhasil dicatat.');
    }

    private function validateMember(Request $request, ?Member $member = null): array
    {
        $memberId = $member?->id ?? 'NULL';
        if ($member) {
            $member->loadMissing('user');
        }
        $userId = $member?->user?->id;

        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:50', 'unique:members,nik,' . $memberId],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email,' . $memberId],
            'login' => ['required', 'string', 'max:100', 'unique:members,login,' . $memberId],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'company_unit' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]);

        $existingEmailUser = User::query()
            ->where('email', $validated['email'])
            ->when($userId, fn ($query) => $query->whereKeyNot($userId))
            ->first();

        if ($existingEmailUser && ! $this->canReuseMemberAccount($existingEmailUser, $member)) {
            throw ValidationException::withMessages([
                'email' => 'Email login ini sudah dipakai akun lain.',
            ]);
        }

        $existingLoginUser = User::query()
            ->where('login', $validated['login'])
            ->when($userId, fn ($query) => $query->whereKeyNot($userId))
            ->first();

        if ($existingLoginUser && ! $this->canReuseMemberAccount($existingLoginUser, $member)) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai akun lain.',
            ]);
        }

        $validated['membership_type'] = $member?->membership_type ?: 'anggota_biasa';

        return $validated;
    }

    private function extractMemberPayload(array $validated): array
    {
        unset($validated['password'], $validated['password_confirmation']);

        return $validated;
    }

    private function syncMemberUser(Member $member, array $validated): void
    {
        $member->loadMissing('user');

        $user = $member->user
            ?? User::query()->where('member_id', $member->id)->first()
            ?? User::query()
                ->whereHas('official', fn ($query) => $query->where('member_id', $member->id))
                ->first()
            ?? User::query()->firstOrNew(['login' => $validated['login']]);

        if ($user->exists && $user->member_id && (int) $user->member_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai anggota lain.',
            ]);
        }

        if ($user->exists && $user->official_id && $user->official?->member_id && (int) $user->official->member_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'login' => 'Login ini sudah dipakai pengurus lain.',
            ]);
        }

        $payload = [
            'name' => $member->name,
            'login' => $validated['login'],
            'email' => $validated['email'],
            'role' => 'member',
            'member_id' => $member->id,
            'is_active' => true,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if (! $user->exists && empty($payload['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Password wajib diisi untuk akun baru.',
            ]);
        }

        $user->fill($payload)->save();
    }

    private function canReuseMemberAccount(User $user, ?Member $member): bool
    {
        if (! $member) {
            return false;
        }

        $user->loadMissing('official');

        return (int) $user->member_id === (int) $member->id
            || ((int) ($user->official?->member_id ?? 0) === (int) $member->id);
    }

    private function memberQuery(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        return Member::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_unit', 'like', "%{$search}%")
                        ->orWhere('npwp', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), function ($query) use ($status) {
                $query->where('status', $status);
            });
    }

    private function loadMemberDetail(int $memberId): ?Member
    {
        $member = Member::with([
            'savings.savingType',
            'loans',
            'retailTransactions',
            'memberLedgers' => fn ($query) => $query->latest('entry_date')->limit(8),
        ])->find($memberId);

        if (!$member) {
            return null;
        }

        $member->setAttribute(
            'savings_by_type',
            $member->savings
                ->groupBy(fn (Saving $saving) => $saving->savingType?->name ?? 'Lainnya')
                ->map(fn ($items, $type) => [
                    'type' => $type,
                    'total' => (float) $items->sum('amount'),
                ])
                ->values()
        );

        $member->setAttribute(
            'active_loans',
            $member->loans
                ->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])
                ->sortByDesc('created_at')
                ->values()
        );

        $member->setAttribute('total_savings', (float) $member->savings->sum('amount'));
        $member->setAttribute(
            'total_loans',
            (float) $member->loans->where('status', '!=', 'completed')->sum('remaining_balance')
        );
        $member->setAttribute('total_retail_receivable', (float) $member->balance_receivable);
        $ledgerByScope = $member->memberLedgers
            ->groupBy('ledger_scope')
            ->map(fn ($items) => (float) ($items->sortByDesc('entry_date')->first()?->balance ?? 0));

        $member->setAttribute('savings_ledger_balance', (float) ($ledgerByScope->get('savings') ?? 0));
        $member->setAttribute('loan_payable_balance', (float) $member->balance_payable);
        $member->setAttribute('retail_receivable_balance', (float) $member->balance_receivable);
        $member->setAttribute('loan_count', (int) $member->loans->count());
        $member->setAttribute('saving_count', (int) $member->savings->count());
        $member->setAttribute('retail_transaction_count', (int) $member->retailTransactions->count());

        $recentSavings = $member->savings->sortByDesc('deposit_date')->take(4)->map(function (Saving $saving) {
            return [
                'group' => 'Simpanan',
                'label' => 'Setoran ' . ($saving->savingType?->name ?? 'Simpanan'),
                'date' => $saving->deposit_date,
                'amount' => (float) $saving->amount,
                'status' => $saving->is_posted ? 'Diposting' : 'Draft',
                'note' => ucfirst($saving->payment_method),
            ];
        });

        $recentLoanPayments = LoanPayment::with('loan')
            ->whereHas('loan', fn ($query) => $query->where('member_id', $member->id))
            ->latest('payment_date')
            ->take(4)
            ->get()
            ->map(function (LoanPayment $payment) {
                return [
                    'group' => 'Pembayaran',
                    'label' => 'Pembayaran pinjaman #' . $payment->loan_id,
                    'date' => $payment->payment_date,
                    'amount' => (float) $payment->total_paid,
                    'status' => $payment->is_posted ? 'Diposting' : 'Draft',
                    'note' => ucfirst($payment->payment_method),
                ];
            });

        $recentLoans = $member->loans
            ->sortByDesc('created_at')
            ->take(4)
            ->map(function (Loan $loan) {
                return [
                    'group' => 'Pinjaman',
                    'label' => 'Pengajuan pinjaman #' . $loan->id,
                    'date' => $loan->created_at,
                    'amount' => (float) $loan->principal_amount,
                    'status' => ucfirst($loan->status),
                    'note' => 'Tenor ' . $loan->tenor_months . ' bulan',
                ];
            });

        $recentRetail = $member->retailTransactions
            ->sortByDesc('transaction_date')
            ->take(4)
            ->map(function (RetailTransaction $transaction) {
                return [
                    'group' => 'Retail',
                    'label' => 'Transaksi retail ' . ucfirst($transaction->category),
                    'date' => $transaction->transaction_date,
                    'amount' => (float) $transaction->total_amount,
                    'status' => ucfirst($transaction->status),
                    'note' => ucfirst($transaction->payment_method),
                ];
            });

        $recentReceivablePayments = MemberReceivablePayment::query()
            ->where('member_id', $member->id)
            ->latest('payment_date')
            ->take(4)
            ->get()
            ->map(function (MemberReceivablePayment $payment) {
                return [
                    'group' => 'Pelunasan Piutang Retail',
                    'label' => 'Pelunasan piutang retail',
                    'date' => $payment->payment_date,
                    'amount' => (float) $payment->amount,
                    'status' => 'Tercatat',
                    'note' => ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
                ];
            });

        $recentActivity = $recentSavings
            ->concat($recentLoanPayments)
            ->concat($recentLoans)
            ->concat($recentRetail)
            ->concat($recentReceivablePayments)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        $member->setAttribute('recent_activity', $recentActivity);
        $member->setAttribute('activity_groups', $this->buildActivityGroups($recentActivity));
        $member->setAttribute('ledger_entries', $this->buildLedgerEntries($member->memberLedgers));

        return $member;
    }

    private function buildActivityGroups(Collection $activities): Collection
    {
        $groups = ['Simpanan', 'Pinjaman', 'Pembayaran', 'Retail', 'Pelunasan Piutang Retail'];

        return collect($groups)->map(function (string $group) use ($activities) {
            return [
                'title' => $group,
                'items' => $activities->where('group', $group)->take(4)->values(),
            ];
        });
    }

    private function buildLedgerEntries(Collection $entries): Collection
    {
        return $entries->map(function ($entry) {
            return [
                'date' => $entry->entry_date,
                'scope' => $this->formatLedgerScope((string) $entry->ledger_scope),
                'type' => $this->formatTransactionType((string) $entry->transaction_type),
                'memo' => $entry->memo ?: 'Mutasi anggota',
                'debit' => (float) $entry->debit,
                'credit' => (float) $entry->credit,
                'balance' => (float) $entry->balance,
            ];
        })->values();
    }

    private function formatTransactionType(string $type): string
    {
        return match ($type) {
            'savings' => 'Simpanan',
            'loan_payment' => 'Pembayaran Pinjaman',
            'retail_transaction' => 'Transaksi Retail',
            'loan_disbursement' => 'Pencairan Pinjaman',
            'unit_usaha_sale' => 'Penjualan POS',
            'member_receivable_payment' => 'Pelunasan Piutang Retail',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function formatLedgerScope(string $scope): string
    {
        return match ($scope) {
            'savings' => 'Simpanan',
            'loan_payable' => 'Pinjaman',
            'retail_receivable' => 'Piutang Retail',
            default => ucwords(str_replace('_', ' ', $scope)),
        };
    }
}
