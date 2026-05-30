@extends('layouts.app')

@php
    $activeCompanies = $companies->where('is_active', true)->count();
    $inactiveCompanies = $companies->where('is_active', false)->count();
    $withBillingEmail = $companies->filter(fn ($company) => filled($company->billing_email))->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <form method="POST" action="{{ route('piutang-usaha.companies.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Perusahaan</label>
                            <input
                                name="code"
                                value="{{ old('code', $companyCode) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="CUS-20260528-001"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Perusahaan</label>
                            <input
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="PT Contoh Mitra Abadi"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">PIC / Kontak Utama</label>
                            <input
                                name="pic_name"
                                value="{{ old('pic_name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Nama PIC perusahaan"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Telepon</label>
                            <input
                                name="phone"
                                value="{{ old('phone') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="0812xxxx atau telepon kantor"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Email Penagihan</label>
                            <input
                                name="billing_email"
                                type="email"
                                value="{{ old('billing_email') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="billing@perusahaan.co.id"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">NPWP</label>
                            <input
                                name="npwp"
                                value="{{ old('npwp') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="00.000.000.0-000.000"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Alamat</label>
                            <textarea
                                name="address"
                                rows="4"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Alamat lengkap perusahaan"
                            >{{ old('address') }}</textarea>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Termin Pembayaran (Hari)</label>
                                <input
                                    name="payment_term_days"
                                    type="number"
                                    min="0"
                                    value="{{ old('payment_term_days', 30) }}"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                    placeholder="30"
                                >
                            </div>
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                                Perusahaan aktif dan bisa dipakai pada kontrak / invoice
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea
                            name="notes"
                            rows="3"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan tambahan untuk kebutuhan penagihan, dokumen, atau administrasi pelanggan."
                        >{{ old('notes') }}</textarea>
                    </div>

                    <div class="grid gap-3 border-t border-slate-100 pt-2">
                        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                            Simpan Data Perusahaan
                        </button>
                        <button
                            type="button"
                            id="open-company-list"
                            class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500"
                        >
                            View Data
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="company-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-company-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Perusahaan</h3>
                    </div>
                    <button
                        type="button"
                        id="close-company-list"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup data perusahaan"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $companies->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Aktif</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $activeCompanies }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Email Billing</p>
                            <p class="mt-2 text-xl font-bold text-cyan-800">{{ $withBillingEmail }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Nonaktif</p>
                            <p class="mt-2 text-xl font-bold text-slate-800">{{ $inactiveCompanies }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Perusahaan</th>
                                        <th class="px-5 py-3">PIC</th>
                                        <th class="px-5 py-3">Kontak</th>
                                        <th class="px-5 py-3">Termin</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($companies as $company)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $company->code }}</td>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $company->name }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $company->address ?: 'Alamat belum diisi.' }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $company->pic_name ?: '-' }}</td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm text-slate-700">{{ $company->phone ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $company->billing_email ?: 'Email billing belum diisi' }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $company->payment_term_days }} hari</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $company->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $company->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data perusahaan yang tersimpan.</td>
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
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('company-list-modal');
            const openButton = document.getElementById('open-company-list');
            const closeButton = document.getElementById('close-company-list');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-company-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
