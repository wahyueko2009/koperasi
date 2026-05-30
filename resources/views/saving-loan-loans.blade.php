@extends('layouts.app')

@php
    $title = $loanSectionTitle . ' - Simpan Pinjam';
    $sectionLabel = 'Operasional Koperasi';
    $pageTitle = 'Pinjaman';
    $pageDescription = $loanSectionDescription;
@endphp

@section('content')
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Area Pinjaman</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ $loanSectionTitle }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $loanSectionDescription }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        <th class="px-6 py-4">Nomor</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Pokok</th>
                        <th class="px-6 py-4">Sisa</th>
                        <th class="px-6 py-4">Status</th>
                        @if (!empty($loanSectionActionable))
                            <th class="px-6 py-4">Tahap Approval</th>
                            <th class="px-6 py-4">Aksi</th>
                        @elseif (($loanSectionTitle ?? '') === 'Pencairan Pinjaman')
                            <th class="px-6 py-4">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($loanSectionItems as $loan)
                        @php
                            $statusClasses = $loan->display_status_class ?? 'bg-amber-100 text-amber-700';
                            $loanDetail = [
                                'loan_number' => $loan->application_number ?: 'PJN-' . str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT),
                                'loan_type' => $loan->loan_type_label,
                                'member' => $loan->member->name,
                                'nik' => $loan->member->nik,
                                'principal' => (float) $loan->principal_amount,
                                'remaining' => (float) $loan->remaining_balance,
                                'monthly_payment' => (float) $loan->monthly_payment,
                                'status' => $loan->display_status_label ?? $loan->status_label,
                                'application_status' => $loan->application_status_label,
                                'tenor' => $loan->tenor_months . ' bulan',
                                'approval_date' => optional($loan->approval_date)?->format('d M Y') ?? '-',
                                'submission_date' => optional($loan->submission_date)?->format('d M Y') ?? '-',
                                'maturity_date' => optional($loan->maturity_date)?->format('d M Y') ?? '-',
                                'purpose' => $loan->purpose ?? '-',
                                'notes' => $loan->notes ?? '-',
                                'current_approval_level' => $loan->current_approval_level_label ?? '-',
                                'progress_steps' => $loan->progress_steps ?? [],
                            ];
                            $bendaharaApproval = $loan->approvals->firstWhere('approval_level', 'bendahara');
                            $ketuaApproval = $loan->approvals->firstWhere('approval_level', 'ketua_koperasi');
                            $approvalBadgeClasses = static function (?string $status): string {
                                return match ($status) {
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'queued' => 'bg-slate-100 text-slate-600',
                                    'skipped' => 'bg-slate-200 text-slate-500',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            };
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50" data-loan-detail='@json($loanDetail)'>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $loan->application_number ?: 'PJN-' . str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT) }}</p>
                                <p class="mt-1 text-xs text-slate-400">Klik baris untuk detail</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-900">{{ $loan->loan_type_label }}</p>
                                <p class="mt-1 text-sm text-slate-500">Tenor {{ $loan->tenor_months }} bulan</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $loan->member->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $loan->member->nik }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $loan->principal_amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $loan->remaining_balance, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $loan->display_status_label ?? $loan->status_label }}
                                </span>
                            </td>
                            @if (!empty($loanSectionActionable))
                                <td class="px-6 py-4">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-medium text-slate-700">Bendahara</span>
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $approvalBadgeClasses($bendaharaApproval?->status) }}">
                                                {{ $bendaharaApproval?->status_label ?? 'Belum ada' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="font-medium text-slate-700">Ketua Koperasi</span>
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $approvalBadgeClasses($ketuaApproval?->status) }}">
                                                {{ $ketuaApproval?->status_label ?? 'Belum ada' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div data-prevent-row-modal="true" class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Tahap aktif</p>
                                        <p class="text-sm font-semibold text-slate-900">{{ $loan->current_approval_level_label ?? 'Selesai' }}</p>
                                        @if ($loan->can_be_approved_by_current_user)
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('simpan-pinjam.loan.approval.process', $loan) }}" data-prevent-row-modal="true">
                                                    @csrf
                                                    <input type="hidden" name="decision" value="approved">
                                                    <button class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Setujui</button>
                                                </form>
                                                <button
                                                    type="button"
                                                    class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700"
                                                    data-prevent-row-modal="true"
                                                    data-open-reject-modal="true"
                                                    data-reject-action="{{ route('simpan-pinjam.loan.approval.process', $loan) }}"
                                                    data-reject-loan="{{ $loan->application_number ?: 'PJN-' . str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT) }}"
                                                >
                                                    Tolak
                                                </button>
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-400">Menunggu pejabat yang sesuai</p>
                                        @endif
                                    </div>
                                </td>
                            @elseif (($loanSectionTitle ?? '') === 'Pencairan Pinjaman')
                                <td class="px-6 py-4">
                                    <div data-prevent-row-modal="true" class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Siap diproses</p>
                                        <p class="text-sm font-semibold text-slate-900">{{ optional($loan->approval_date)->format('d M Y') ?? 'Belum ada tanggal approval' }}</p>
                                        @if ($loan->can_be_disbursed)
                                            <form method="POST" action="{{ route('simpan-pinjam.loan.disburse.process', $loan) }}" data-prevent-row-modal="true">
                                                @csrf
                                                <button class="rounded-xl bg-sky-600 px-4 py-2 text-xs font-semibold text-white hover:bg-sky-700">
                                                    Proses Pencairan
                                                </button>
                                            </form>
                                        @else
                                            <p class="text-xs text-slate-400">Belum siap dicairkan</p>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($loanSectionActionable) ? '8' : (($loanSectionTitle ?? '') === 'Pencairan Pinjaman' ? '7' : '6') }}" class="px-6 py-10 text-center text-sm text-slate-500">{{ $loanSectionEmptyMessage }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('partials.saving-loan-transaction-modals')

    <div id="loan-detail-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-6">
        <div class="flex h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Pinjaman</p>
                    <h2 id="loan-detail-number" class="text-2xl font-bold text-slate-900">PJN-000001</h2>
                </div>
                <button type="button" data-close-loan-detail class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-6">
                <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Anggota</p>
                    <p id="loan-detail-member" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                    <p id="loan-detail-nik" class="mt-1 text-sm text-slate-500">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <p id="loan-detail-status" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Jenis Pinjaman</p>
                    <p id="loan-detail-type" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status Aplikasi</p>
                    <p id="loan-detail-application-status" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tahap Approval</p>
                    <p id="loan-detail-current-approval" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pokok Pinjaman</p>
                    <p id="loan-detail-principal" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Sisa Pinjaman</p>
                    <p id="loan-detail-remaining" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Angsuran Bulanan</p>
                    <p id="loan-detail-monthly" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tenor</p>
                    <p id="loan-detail-tenor" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tanggal Pengajuan</p>
                    <p id="loan-detail-submission" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tanggal Approval</p>
                    <p id="loan-detail-approval" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Jatuh Tempo</p>
                    <p id="loan-detail-maturity" class="mt-2 text-lg font-semibold text-slate-900">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Progress Pengajuan</p>
                    <div id="loan-detail-progress" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tujuan Pinjaman</p>
                    <p id="loan-detail-purpose" class="mt-2 text-base text-slate-700">-</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4 md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Catatan</p>
                    <p id="loan-detail-notes" class="mt-2 text-base text-slate-700">-</p>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loan-reject-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-8">
        <div class="w-full max-w-xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tolak Pengajuan</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900" id="loan-reject-title">Pengajuan Pinjaman</h2>
                </div>
                <button type="button" data-close-reject-modal class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="loan-reject-form" method="POST" class="px-6 py-6">
                @csrf
                <input type="hidden" name="decision" value="rejected">
                <input type="hidden" name="loan_number" id="loan-reject-loan-number" value="{{ old('loan_number') }}">
                <input type="hidden" name="reject_action" id="loan-reject-action" value="{{ old('reject_action') }}">
                <div>
                    <label for="loan-reject-notes" class="mb-2 block text-sm font-semibold text-slate-700">Informasi kenapa ditolak</label>
                    <textarea
                        id="loan-reject-notes"
                        name="notes"
                        rows="5"
                        required
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        placeholder="Tuliskan alasan penolakan pengajuan ini"
                    >{{ old('decision') === 'rejected' ? old('notes') : '' }}</textarea>
                    <p class="mt-2 text-xs text-slate-500">Jika pengajuan ditolak, proses approval tidak akan lanjut ke tahap berikutnya.</p>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" data-close-reject-modal class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.saving-loan-transaction-scripts')
    <script>
        (() => {
            const modal = document.getElementById('loan-detail-modal');
            const rejectModal = document.getElementById('loan-reject-modal');
            const rejectForm = document.getElementById('loan-reject-form');
            const rejectTitle = document.getElementById('loan-reject-title');
            const rejectNotes = document.getElementById('loan-reject-notes');
            const rejectLoanNumber = document.getElementById('loan-reject-loan-number');
            const rejectAction = document.getElementById('loan-reject-action');
            const rows = document.querySelectorAll('[data-loan-detail]');
            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value || 0)}`;
            let preventRowOpen = false;

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            const openRejectModal = (action, loanNumber) => {
                rejectForm.action = action;
                rejectAction.value = action;
                rejectLoanNumber.value = loanNumber || '';
                rejectTitle.textContent = loanNumber || 'Pengajuan Pinjaman';
                rejectModal.classList.remove('hidden');
                rejectModal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                rejectNotes.focus();
            };

            const closeRejectModal = () => {
                rejectModal.classList.add('hidden');
                rejectModal.classList.remove('flex');
                rejectForm.action = '';
                document.body.classList.remove('overflow-hidden');
            };

            rows.forEach((row) => {
                row.addEventListener('click', () => {
                    if (preventRowOpen) {
                        preventRowOpen = false;
                        return;
                    }

                    const detail = JSON.parse(row.dataset.loanDetail);
                    document.getElementById('loan-detail-number').textContent = detail.loan_number || '-';
                    document.getElementById('loan-detail-member').textContent = detail.member || '-';
                    document.getElementById('loan-detail-nik').textContent = detail.nik || '-';
                    document.getElementById('loan-detail-status').textContent = detail.status || '-';
                    document.getElementById('loan-detail-type').textContent = detail.loan_type || '-';
                    document.getElementById('loan-detail-application-status').textContent = detail.application_status || '-';
                    document.getElementById('loan-detail-current-approval').textContent = detail.current_approval_level || '-';
                    document.getElementById('loan-detail-principal').textContent = formatCurrency(detail.principal);
                    document.getElementById('loan-detail-remaining').textContent = formatCurrency(detail.remaining);
                    document.getElementById('loan-detail-monthly').textContent = formatCurrency(detail.monthly_payment);
                    document.getElementById('loan-detail-tenor').textContent = detail.tenor || '-';
                    document.getElementById('loan-detail-submission').textContent = detail.submission_date || '-';
                    document.getElementById('loan-detail-approval').textContent = detail.approval_date || '-';
                    document.getElementById('loan-detail-maturity').textContent = detail.maturity_date || '-';
                    document.getElementById('loan-detail-purpose').textContent = detail.purpose || '-';
                    document.getElementById('loan-detail-notes').textContent = detail.notes || '-';
                    document.getElementById('loan-detail-progress').innerHTML = (detail.progress_steps || []).map((step) => `
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ${step.done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                            ${step.label}
                        </span>
                    `).join('');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-prevent-row-modal="true"]').forEach((element) => {
                element.addEventListener('click', () => {
                    preventRowOpen = true;
                });
            });

            document.querySelectorAll('[data-open-reject-modal="true"]').forEach((button) => {
                button.addEventListener('click', () => {
                    openRejectModal(button.dataset.rejectAction, button.dataset.rejectLoan);
                });
            });

            document.querySelectorAll('[data-close-loan-detail]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('[data-close-reject-modal]').forEach((button) => {
                button.addEventListener('click', closeRejectModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            rejectModal?.addEventListener('click', (event) => {
                if (event.target === rejectModal) {
                    closeRejectModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }

                if (event.key === 'Escape' && rejectModal && !rejectModal.classList.contains('hidden')) {
                    closeRejectModal();
                }
            });

            @if ($errors->has('notes') && old('decision') === 'rejected')
                openRejectModal(@json(old('reject_action')), @json(old('loan_number', 'Pengajuan Pinjaman')));
            @endif
        })();
    </script>
@endpush
