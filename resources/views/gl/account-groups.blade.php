@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('gl.account-groups.store') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Kelompok</label>
                        <input name="code" value="{{ old('code', $groupCode) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="GRP-001">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Kelompok</label>
                        <input name="name" value="{{ old('name') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Aset Lancar">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tipe Akun</label>
                        <select name="account_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            @foreach (['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Modal', 'income' => 'Pendapatan', 'expense' => 'Beban'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('account_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Saldo Normal</label>
                        <select name="normal_balance" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            @foreach (['debit' => 'Debit', 'credit' => 'Kredit'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('normal_balance') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-[1fr_0.8fr]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea name="description" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Kelompok dipakai untuk akun kas, bank, dan setara kas.">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                            Kelompok akun aktif
                        </label>
                    </div>
                </div>

                <div class="grid gap-3 border-t border-slate-100 pt-2">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan Kelompok Akun</button>
                    <button type="button" id="open-group-list" class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500">Lihat Data</button>
                </div>
            </form>
        </section>
    </div>

    <div id="group-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-group-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Kelompok Akun</h3>
                    </div>
                    <button type="button" id="close-group-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">X</button>
                </div>

                <div class="max-h-[72vh] overflow-y-scroll overflow-x-hidden px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-4 py-3">Kode</th>
                                        <th class="px-4 py-3">Kelompok</th>
                                        <th class="px-4 py-3">Tipe</th>
                                        <th class="px-4 py-3">Saldo</th>
                                        <th class="px-4 py-3">Akun</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($groups as $group)
                                        <tr class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $group->code }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $group->name }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ ucfirst($group->account_type) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ ucfirst($group->normal_balance) }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $group->accounts_count }}</td>
                                            <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $group->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada kelompok akun.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('group-list-modal');
            const openButton = document.getElementById('open-group-list');
            const closeButton = document.getElementById('close-group-list');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-group-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
