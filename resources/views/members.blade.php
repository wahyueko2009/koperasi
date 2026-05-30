@extends('layouts.app')

@php
    $title = 'Manage Anggota - Koperasi Digital Mandiri';
    $sectionLabel = 'Pengaturan Sistem';
    $pageTitle = 'Manage Anggota';
    $pageDescription = 'Kelola data anggota koperasi, buka detail, lalu tambah atau edit anggota dari satu area yang rapi.';
    $headerTabs = [
        [
            'label' => 'Manage Anggota',
            'route' => 'members',
            'active' => str_starts_with((string) request()->route()?->getName(), 'members'),
        ],
        [
            'label' => 'Manage Pengurus',
            'route' => 'settings.officials',
            'active' => str_starts_with((string) request()->route()?->getName(), 'settings.'),
        ],
    ];
    $selectedMemberId = $selectedMember?->id;
@endphp

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Total Anggota</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($memberStats['total']) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Status Aktif</p>
            <p class="mt-3 text-3xl font-bold text-emerald-600">{{ number_format($memberStats['active']) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Nonaktif</p>
            <p class="mt-3 text-3xl font-bold text-amber-600">{{ number_format($memberStats['inactive']) }}</p>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-500">Suspended</p>
            <p class="mt-3 text-3xl font-bold text-rose-600">{{ number_format($memberStats['suspended']) }}</p>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form method="GET" action="{{ route('members') }}" class="grid gap-3 md:grid-cols-[1.3fr_0.7fr_auto]">
                    <div>
                        <label for="search" class="mb-2 block text-sm font-medium text-slate-700">Cari anggota</label>
                        <input
                            id="search"
                            name="search"
                            value="{{ $filters['search'] }}"
                            type="text"
                            placeholder="Cari nama, NIK, email, atau telepon"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                        >
                    </div>
                    <div>
                        <label for="status" class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20"
                        >
                            <option value="">Semua status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                            <option value="suspended" @selected($filters['status'] === 'suspended')>Suspended</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button class="rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Terapkan
                        </button>
                        <a href="{{ route('members') }}" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('members.export.excel', array_filter(['search' => $filters['search'], 'status' => $filters['status']])) }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <i class="fas fa-file-excel"></i>
                        Download Excel
                    </a>
                    <a href="{{ route('members.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-accent px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-accent/20 transition hover:bg-teal-700">
                        <i class="fas fa-plus"></i>
                        Tambah Data Anggota
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Daftar Manage Anggota</h2>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-6 py-3">Anggota</th>
                            <th class="px-6 py-3">Kontak</th>
                            <th class="px-6 py-3">Unit Kerja</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($members as $member)
                            @php
                                $statusClasses = match ($member->status) {
                                    'active' => 'bg-emerald-100 text-emerald-700',
                                    'inactive' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-rose-100 text-rose-700',
                                };
                            @endphp
                            <tr class="cursor-pointer hover:bg-slate-50" data-open-member-modal="{{ $member->id }}">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 font-bold text-slate-700">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $member->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $member->nik }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">
                                    <p>{{ $member->email }}</p>
                                    <p class="mt-1 text-slate-400">{{ $member->phone ?: 'Belum diisi' }}</p>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-600">
                                    <p class="font-medium text-slate-900">{{ $member->company_unit ?: 'Belum diisi' }}</p>
                                    <p class="mt-1 text-slate-400">{{ $member->account_number ?: 'Rekening belum diisi' }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3 text-sm">
                                        <a href="{{ route('members.edit', $member) }}" class="font-semibold text-accent hover:text-teal-700" data-prevent-row-modal="true">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                    Belum ada anggota yang cocok dengan filter saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $members->links() }}
            </div>
        </div>
    </div>

    <div id="member-modal-root" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/60 px-4 py-6">
        <div class="flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between rounded-t-[2rem] border-b border-slate-200 bg-white px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Detail Anggota</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900" id="member-modal-name">Anggota</h2>
                    <p class="mt-1 text-sm text-slate-500" id="member-modal-email">-</p>
                </div>
                <div class="flex items-center gap-3">
                    <div id="member-modal-edit-slot"></div>
                    <button
                        type="button"
                        id="member-modal-close"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50"
                        aria-label="Tutup detail anggota"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="member-modal-content" class="flex-1 overflow-y-auto px-6 py-6"></div>
        </div>
    </div>

    <div id="member-modal-templates" class="hidden">
        @foreach ($memberDetails as $detailMember)
            @php
                $selectedStatusClasses = match ($detailMember->status) {
                    'active' => 'bg-emerald-100 text-emerald-700',
                    'inactive' => 'bg-amber-100 text-amber-700',
                    default => 'bg-rose-100 text-rose-700',
                };
            @endphp

            <template data-member-template="{{ $detailMember->id }}">
                <div data-member-name="{{ $detailMember->name }}" data-member-email="{{ $detailMember->email }}" data-member-edit-url="{{ route('members.edit', $detailMember) }}">
                    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                        <div class="space-y-6">
                            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-primary via-slate-900 to-accent text-white">
                                <div class="px-6 py-6">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white/10 text-2xl font-bold">
                                            {{ strtoupper(substr($detailMember->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Profil Singkat</p>
                                            <h3 class="mt-2 text-2xl font-bold">{{ $detailMember->name }}</h3>
                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $selectedStatusClasses }}">
                                                    {{ ucfirst($detailMember->status) }}
                                                </span>
                                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                                                    NIK {{ $detailMember->nik }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Telepon</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->phone ?: 'Belum diisi' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Nama Pasangan</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->spouse_name ?: 'Belum diisi' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">NPWP</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->npwp ?: 'Belum diisi' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Terdaftar</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->created_at?->format('d M Y') }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Company/Unit Kerja</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->company_unit ?: 'Belum diisi' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">No Rekening</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->account_number ?: 'Belum diisi' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 sm:col-span-2">
                                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Alamat</p>
                                            <p class="mt-2 font-semibold">{{ $detailMember->address ?: 'Belum diisi' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-3xl bg-primary p-5 text-white shadow-sm">
                                    <p class="text-sm text-slate-300">Total Simpanan</p>
                                    <p class="mt-3 text-3xl font-bold">Rp {{ number_format($detailMember->total_savings, 0, ',', '.') }}</p>
                                    <p class="mt-2 text-sm text-slate-300">{{ $detailMember->saving_count }} transaksi simpanan tercatat.</p>
                                </div>
                                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-sm text-slate-500">Sisa Pinjaman</p>
                                    <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format($detailMember->total_loans, 0, ',', '.') }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ $detailMember->loan_count }} riwayat pinjaman tercatat.</p>
                                </div>
                                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-sm text-slate-500">Piutang Retail</p>
                                    <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format($detailMember->retail_receivable_balance, 0, ',', '.') }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ $detailMember->retail_transaction_count }} transaksi retail.</p>
                                </div>
                                <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                                    <p class="text-sm text-slate-500">Hutang Anggota</p>
                                    <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format($detailMember->loan_payable_balance, 0, ',', '.') }}</p>
                                    <p class="mt-2 text-sm text-slate-500">Sisa kewajiban pinjaman anggota ke koperasi.</p>
                                </div>
                            </div>

                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="text-lg font-bold text-slate-900">Ringkasan Simpanan</h3>
                                <p class="mt-1 text-sm text-slate-500">Komposisi setoran anggota per jenis simpanan.</p>
                                <div class="mt-4 space-y-3">
                                    @forelse ($detailMember->savings_by_type as $saving)
                                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                            <p class="text-sm font-medium text-slate-700">{{ $saving['type'] }}</p>
                                            <p class="text-sm font-semibold text-slate-900">Rp {{ number_format($saving['total'], 0, ',', '.') }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">Belum ada transaksi simpanan untuk anggota ini.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="text-lg font-bold text-slate-900">Pinjaman Aktif</h3>
                                <p class="mt-1 text-sm text-slate-500">Posisi pinjaman yang masih berjalan atau menunggu proses.</p>
                                <div class="mt-4 space-y-3">
                                    @forelse ($detailMember->active_loans as $loan)
                                        <div class="rounded-2xl border border-slate-200 px-4 py-4">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="font-semibold text-slate-900">Pinjaman #{{ $loan->id }}</p>
                                                    <p class="text-sm text-slate-500">{{ ucfirst($loan->status) }} | tenor {{ $loan->tenor_months }} bulan</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm text-slate-500">Sisa saldo</p>
                                                    <p class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $loan->remaining_balance, 0, ',', '.') }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Pokok</p>
                                                    <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format((float) $loan->principal_amount, 0, ',', '.') }}</p>
                                                </div>
                                                <div class="rounded-2xl bg-slate-50 px-3 py-2">
                                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Angsuran Bulanan</p>
                                                    <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format((float) $loan->monthly_payment, 0, ',', '.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">Tidak ada pinjaman aktif.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="text-lg font-bold text-slate-900">Riwayat Aktivitas</h3>
                                <p class="mt-1 text-sm text-slate-500">Aktivitas terbaru dibagi per jenis transaksi supaya lebih mudah dipantau.</p>

                                <div class="mt-5 space-y-5">
                                    @forelse ($detailMember->activity_groups as $group)
                                        <div class="rounded-2xl border border-slate-200">
                                            <div class="border-b border-slate-200 px-4 py-3">
                                                <p class="text-sm font-semibold text-slate-900">{{ $group['title'] }}</p>
                                            </div>
                                            <div class="divide-y divide-slate-100">
                                                @forelse ($group['items'] as $activity)
                                                    <div class="flex items-start justify-between gap-3 px-4 py-3">
                                                        <div>
                                                            <p class="font-medium text-slate-900">{{ $activity['label'] }}</p>
                                                            <p class="mt-1 text-sm text-slate-500">
                                                                {{ $activity['date']?->format('d M Y') ?? '-' }} | {{ $activity['status'] }} | {{ $activity['note'] ?? '-' }}
                                                            </p>
                                                        </div>
                                                        <p class="text-sm font-semibold text-slate-900">Rp {{ number_format($activity['amount'], 0, ',', '.') }}</p>
                                                    </div>
                                                @empty
                                                    <div class="px-4 py-4 text-sm text-slate-500">
                                                        Belum ada aktivitas pada kategori ini.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">Belum ada aktivitas yang tercatat.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="text-lg font-bold text-slate-900">Pelunasan Piutang Retail</h3>
                                <p class="mt-1 text-sm text-slate-500">Gunakan form ini untuk mencatat pembayaran piutang retail anggota agar saldo dan buku besar ikut turun.</p>

                                <form method="POST" action="{{ route('members.retail-receivable-payments.store', $detailMember) }}" class="mt-5 space-y-4">
                                    @csrf
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Bayar</label>
                                            <input name="payment_date" type="date" value="{{ now()->toDateString() }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Pembayaran</label>
                                            <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                                <option value="cash">Tunai</option>
                                                <option value="transfer">Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nominal Pelunasan</label>
                                        <input name="amount" type="number" min="0.01" max="{{ (float) $detailMember->retail_receivable_balance }}" step="0.01" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Masukkan nominal pelunasan">
                                        <p class="mt-2 text-xs text-slate-500">Sisa piutang retail saat ini: Rp {{ number_format((float) $detailMember->retail_receivable_balance, 0, ',', '.') }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Catatan pelunasan retail"></textarea>
                                    </div>
                                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                        Simpan Pelunasan Piutang Retail
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <h3 class="text-lg font-bold text-slate-900">Member Ledger</h3>
                                <p class="mt-1 text-sm text-slate-500">Mutasi saldo anggota dari transaksi yang memengaruhi posisi individual.</p>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                                                <th class="px-4 py-3">Tanggal</th>
                                                <th class="px-4 py-3">Scope</th>
                                                <th class="px-4 py-3">Jenis</th>
                                                <th class="px-4 py-3">Memo</th>
                                                <th class="px-4 py-3">Bertambah</th>
                                                <th class="px-4 py-3">Berkurang</th>
                                                <th class="px-4 py-3">Saldo</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($detailMember->ledger_entries as $entry)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $entry['date']?->format('d M Y') ?? '-' }}</td>
                                                    <td class="px-4 py-3 text-sm font-medium text-slate-700">{{ $entry['scope'] }}</td>
                                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $entry['type'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $entry['memo'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">Rp {{ number_format($entry['debit'], 0, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">Rp {{ number_format($entry['credit'], 0, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900">Rp {{ number_format($entry['balance'], 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                                        Belum ada mutasi ledger anggota.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modalRoot = document.getElementById('member-modal-root');
            const modalContent = document.getElementById('member-modal-content');
            const modalName = document.getElementById('member-modal-name');
            const modalEmail = document.getElementById('member-modal-email');
            const editSlot = document.getElementById('member-modal-edit-slot');
            const closeButton = document.getElementById('member-modal-close');
            const templates = new Map();

            document.querySelectorAll('[data-member-template]').forEach((template) => {
                templates.set(template.dataset.memberTemplate, template);
            });

            const openModal = (memberId) => {
                const template = templates.get(String(memberId));
                if (!template) {
                    return;
                }

                const contentNode = template.content.firstElementChild.cloneNode(true);
                modalName.textContent = contentNode.dataset.memberName || 'Anggota';
                modalEmail.textContent = contentNode.dataset.memberEmail || '-';
                editSlot.innerHTML = `
                    <a href="${contentNode.dataset.memberEditUrl}" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Edit
                    </a>
                `;
                modalContent.innerHTML = '';
                modalContent.appendChild(contentNode.firstElementChild);
                modalRoot.classList.remove('hidden');
                modalRoot.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modalRoot.classList.add('hidden');
                modalRoot.classList.remove('flex');
                modalContent.innerHTML = '';
                editSlot.innerHTML = '';
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('[data-open-member-modal]').forEach((element) => {
                element.addEventListener('click', (event) => {
                    if (event.target.closest('[data-prevent-row-modal="true"]') && element === event.currentTarget && element.tagName === 'TR') {
                        return;
                    }

                    openModal(element.dataset.openMemberModal);
                });
            });

            closeButton.addEventListener('click', closeModal);

            modalRoot.addEventListener('click', (event) => {
                if (event.target === modalRoot) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modalRoot.classList.contains('hidden')) {
                    closeModal();
                }
            });

            const selectedMemberId = @json($selectedMemberId);
            if (selectedMemberId) {
                openModal(selectedMemberId);
            }
        })();
    </script>
@endpush
