@extends('layouts.app')

@php
    $activeAccounts = $accounts->where('is_active', true)->count();
    $headerAccounts = $accounts->where('is_header', true)->count();
    $transactionAccounts = $accounts->where('is_header', false)->count();
    $maxLevel = (int) $accounts->max('display_level');
    $typeLabels = [
        'asset' => 'Aset',
        'liability' => 'Kewajiban',
        'equity' => 'Modal',
        'income' => 'Pendapatan',
        'expense' => 'Beban',
    ];
    $typeClasses = [
        'asset' => 'bg-cyan-100 text-cyan-700',
        'liability' => 'bg-amber-100 text-amber-700',
        'equity' => 'bg-violet-100 text-violet-700',
        'income' => 'bg-emerald-100 text-emerald-700',
        'expense' => 'bg-rose-100 text-rose-700',
    ];
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <form method="POST" action="{{ route('gl.accounts.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Akun</label>
                            <input
                                id="account-code"
                                name="code"
                                value="{{ old('code', $accountCode) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="1001"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Akun</label>
                            <input
                                id="account-name"
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Kas Kecil"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tipe Akun</label>
                            <select id="account-type" name="account_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih tipe akun</option>
                                @foreach ($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('account_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kelompok Akun</label>
                            <select id="account-group-id" name="account_group_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih kelompok akun</option>
                                @foreach ($accountGroups as $group)
                                    <option value="{{ $group->id }}" @selected((string) old('account_group_id') === (string) $group->id)>{{ $group->code }} - {{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Saldo Normal</label>
                            <select id="normal-balance" name="normal_balance" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih saldo normal</option>
                                @foreach (['debit' => 'Debit', 'credit' => 'Kredit'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('normal_balance') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1fr_0.9fr]">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Parent Account</label>
                            <select id="parent-id" name="parent_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Tanpa parent / akun level utama</option>
                                @foreach ($parentAccounts as $parentAccount)
                                    <option value="{{ $parentAccount->id }}" @selected((string) old('parent_id') === (string) $parentAccount->id)>
                                        {{ str_repeat('-- ', (int) ($parentAccount->display_level ?? 0)) }}{{ $parentAccount->code }} - {{ $parentAccount->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="parent-account-hint" class="mt-2 text-xs text-slate-500">Pilih akun induk jika ingin membuat sub akun bertingkat.</p>
                        </div>
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <input id="is-header" type="checkbox" name="is_header" value="1" class="rounded border-slate-300" {{ old('is_header') ? 'checked' : '' }}>
                                Akun header / kelompok, bukan akun transaksi
                            </label>
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <input id="is-active" type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                                Akun aktif dan dapat dipakai pada jurnal
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            id="account-description"
                            name="description"
                            rows="3"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan penggunaan akun, contoh: dipakai untuk kas operasional harian."
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="grid gap-3 border-t border-slate-100 pt-2">
                        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                            Simpan Akun / COA
                        </button>
                        <button
                            type="button"
                            id="open-account-list"
                            class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500"
                        >
                            Lihat Data
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="account-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-account-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-auto min-w-[900px] max-w-[95vw] overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Akun / COA</h3>
                    </div>
                    <button
                        type="button"
                        id="close-account-list"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup data akun"
                    >
                        X
                    </button>
                </div>

                <div class="max-h-[72vh] overflow-y-scroll overflow-x-hidden px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="w-auto min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="whitespace-nowrap px-4 py-3">Kode</th>
                                        <th class="whitespace-nowrap px-4 py-3">Akun</th>
                                        <th class="whitespace-nowrap px-4 py-3">Tipe</th>
                                        <th class="whitespace-nowrap px-4 py-3">Kelompok</th>
                                        <th class="whitespace-nowrap px-4 py-3">Saldo</th>
                                        <th class="whitespace-nowrap px-4 py-3">Parent</th>
                                        <th class="whitespace-nowrap px-4 py-3">Status</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($accounts as $account)
                                        <tr class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $account->code }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                <div class="flex items-center gap-2" style="padding-left: {{ (int) ($account->display_level ?? 0) * 16 }}px;">
                                                    <span class="font-semibold text-slate-900">{{ $account->name }}</span>
                                                    @if ($account->is_header)
                                                        <span class="inline-flex rounded-full bg-sky-100 px-2 py-1 text-[11px] font-semibold text-sky-700">Header</span>
                                                    @endif
                                                    @if (($account->display_level ?? 0) > 0)
                                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-700">Sub</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $typeClasses[$account->account_type] ?? 'bg-slate-100 text-slate-700' }}">
                                                    {{ $typeLabels[$account->account_type] ?? ucfirst($account->account_type) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $account->accountGroup?->name ?: '-' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ ucfirst($account->normal_balance) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $account->parent ? $account->parent->code . ' - ' . $account->parent->name : '-' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-2xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                        data-add-sub-account
                                                        data-parent-id="{{ $account->id }}"
                                                        data-parent-code="{{ $account->code }}"
                                                        data-parent-name="{{ $account->name }}"
                                                        data-parent-type="{{ $account->account_type }}"
                                                        data-parent-group-id="{{ $account->account_group_id }}"
                                                        data-parent-normal-balance="{{ $account->normal_balance }}"
                                                    >
                                                        Tambah Sub Akun
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center rounded-2xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                                        data-open-edit-account
                                                        data-account-id="{{ $account->id }}"
                                                        data-account-code="{{ $account->code }}"
                                                        data-account-name="{{ $account->name }}"
                                                        data-account-type="{{ $account->account_type }}"
                                                        data-account-group-id="{{ $account->account_group_id }}"
                                                        data-account-normal-balance="{{ $account->normal_balance }}"
                                                        data-account-parent-id="{{ $account->parent_id }}"
                                                        data-account-is-header="{{ $account->is_header ? '1' : '0' }}"
                                                        data-account-is-active="{{ $account->is_active ? '1' : '0' }}"
                                                        data-account-description="{{ $account->description }}"
                                                    >
                                                        Edit
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada akun / COA yang tersimpan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>

    <div id="edit-account-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-edit-account></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pemeliharaan COA</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Edit Akun / Sub Akun</h3>
                    </div>
                    <button
                        type="button"
                        id="close-edit-account"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup edit akun"
                    >
                        X
                    </button>
                </div>

                <div class="max-h-[76vh] overflow-y-auto px-6 py-6">
                    <form id="edit-account-form" method="POST" action="{{ route('gl.accounts.update', ['account' => 0]) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Akun</label>
                                <input id="edit-code" name="code" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="1001">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Akun</label>
                                <input id="edit-name" name="name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kas Kecil">
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Tipe Akun</label>
                                <select id="edit-account-type" name="account_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih tipe akun</option>
                                    @foreach ($typeLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Kelompok Akun</label>
                                <select id="edit-account-group-id" name="account_group_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih kelompok akun</option>
                                    @foreach ($accountGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->code }} - {{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Saldo Normal</label>
                                <select id="edit-normal-balance" name="normal_balance" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Pilih saldo normal</option>
                                    @foreach (['debit' => 'Debit', 'credit' => 'Kredit'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-[1fr_0.9fr]">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Parent Account</label>
                                <select id="edit-parent-id" name="parent_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                    <option value="">Tanpa parent / akun level utama</option>
                                    @foreach ($parentAccounts as $parentAccount)
                                        <option value="{{ $parentAccount->id }}">
                                            {{ str_repeat('-- ', (int) ($parentAccount->display_level ?? 0)) }}{{ $parentAccount->code }} - {{ $parentAccount->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-slate-500">Akun tidak bisa dipindahkan ke dirinya sendiri atau ke sub akun turunannya.</p>
                            </div>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <input id="edit-is-header" type="checkbox" name="is_header" value="1" class="rounded border-slate-300">
                                    Akun header / kelompok, bukan akun transaksi
                                </label>
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <input id="edit-is-active" type="checkbox" name="is_active" value="1" class="rounded border-slate-300">
                                    Akun aktif dan dapat dipakai pada jurnal
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                            <textarea id="edit-description" name="description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan penggunaan akun."></textarea>
                        </div>

                        <div class="grid gap-3 border-t border-slate-100 pt-2">
                            <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                                Simpan Perubahan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const listModal = document.getElementById('account-list-modal');
            const editModal = document.getElementById('edit-account-modal');
            const openButton = document.getElementById('open-account-list');
            const closeButton = document.getElementById('close-account-list');
            const closeEditButton = document.getElementById('close-edit-account');
            const editForm = document.getElementById('edit-account-form');
            const parentHint = document.getElementById('parent-account-hint');

            const updateBodyLock = () => {
                const anyOpen = [listModal, editModal].some((modal) => modal && !modal.classList.contains('hidden'));
                document.body.classList.toggle('overflow-hidden', anyOpen);
            };

            const showListModal = (show) => {
                listModal?.classList.toggle('hidden', !show);
                updateBodyLock();
            };

            const showEditModal = (show) => {
                editModal?.classList.toggle('hidden', !show);
                updateBodyLock();
            };

            const fillCreateFormForSubAccount = (button) => {
                const parentId = button.dataset.parentId || '';
                const parentCode = button.dataset.parentCode || '';
                const parentName = button.dataset.parentName || '';

                document.getElementById('parent-id').value = parentId;
                document.getElementById('account-type').value = button.dataset.parentType || '';
                document.getElementById('account-group-id').value = button.dataset.parentGroupId || '';
                document.getElementById('normal-balance').value = button.dataset.parentNormalBalance || '';
                document.getElementById('is-header').checked = false;
                document.getElementById('is-active').checked = true;
                document.getElementById('account-code').focus();

                if (parentHint) {
                    parentHint.textContent = `Sub akun baru akan ditempatkan di bawah ${parentCode} - ${parentName}.`;
                }
            };

            const populateEditForm = (button) => {
                if (!button || !editForm) return;

                editForm.action = "{{ url('/buku-besar-gl/daftar-akun-coa') }}/" + (button.dataset.accountId || '');
                document.getElementById('edit-code').value = button.dataset.accountCode || '';
                document.getElementById('edit-name').value = button.dataset.accountName || '';
                document.getElementById('edit-account-type').value = button.dataset.accountType || '';
                document.getElementById('edit-account-group-id').value = button.dataset.accountGroupId || '';
                document.getElementById('edit-normal-balance').value = button.dataset.accountNormalBalance || '';
                document.getElementById('edit-parent-id').value = button.dataset.accountParentId || '';
                document.getElementById('edit-is-header').checked = button.dataset.accountIsHeader === '1';
                document.getElementById('edit-is-active').checked = button.dataset.accountIsActive === '1';
                document.getElementById('edit-description').value = button.dataset.accountDescription || '';
            };

            openButton?.addEventListener('click', () => showListModal(true));
            closeButton?.addEventListener('click', () => showListModal(false));
            closeEditButton?.addEventListener('click', () => showEditModal(false));

            listModal?.querySelectorAll('[data-close-account-list]').forEach((element) => {
                element.addEventListener('click', () => showListModal(false));
            });

            editModal?.querySelectorAll('[data-close-edit-account]').forEach((element) => {
                element.addEventListener('click', () => showEditModal(false));
            });

            document.querySelectorAll('[data-add-sub-account]').forEach((button) => {
                button.addEventListener('click', () => {
                    fillCreateFormForSubAccount(button);
                    showListModal(false);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            document.querySelectorAll('[data-open-edit-account]').forEach((button) => {
                button.addEventListener('click', () => {
                    populateEditForm(button);
                    showEditModal(true);
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                showEditModal(false);
                showListModal(false);
            });
        })();
    </script>
@endpush
