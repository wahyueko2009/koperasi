@extends('layouts.app')

@php
    $activeServices = $services->where('is_active', true)->count();
    $inactiveServices = $services->where('is_active', false)->count();
    $categoriesCount = $services->pluck('category')->filter()->unique()->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Tambah Jasa/Service</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan layanan yang akan dijual di kasir beserta harga, kategori, dan satuannya.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('unit-usaha.master.services.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Jasa</label>
                            <input
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="SRV-FC-A4"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Jasa</label>
                            <input
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Fotokopi B/W A4"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Jasa</label>
                            <select
                                name="category_id"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            >
                                <option value="">Pilih jenis jasa</option>
                                @foreach ($serviceCategories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                        {{ $category->code }} - {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Satuan</label>
                            <input
                                name="unit"
                                value="{{ old('unit', 'lembar') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="lembar / jasa / paket"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Jual</label>
                        <input
                            name="price"
                            value="{{ old('price') }}"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            data-currency-input
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="1500"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            name="description"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan layanan, ukuran kertas, warna, atau keterangan operasional lain."
                        >{{ old('description') }}</textarea>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                        Jasa aktif dan bisa dipilih di kasir
                    </label>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Jasa/Service
                    </button>
                    <button
                        type="button"
                        id="open-service-list"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="service-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-service-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Jasa/Service</h3>
                    </div>
                    <button
                        type="button"
                        id="close-service-list"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup data jasa"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $services->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Aktif</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $activeServices }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Kategori</p>
                            <p class="mt-2 text-xl font-bold text-amber-800">{{ $categoriesCount }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-slate-100 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">Nonaktif</p>
                            <p class="mt-2 text-xl font-bold text-slate-800">{{ $inactiveServices }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Nama Jasa</th>
                                        <th class="px-5 py-3">Kategori</th>
                                        <th class="px-5 py-3">Deskripsi</th>
                                        <th class="px-5 py-3">Satuan</th>
                                        <th class="px-5 py-3">Harga</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($services as $service)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $service->code }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $service->name }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $service->category }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $service->description ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $service->unit }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada jasa/service yang tersimpan.</td>
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
            const modal = document.getElementById('service-list-modal');
            const openButton = document.getElementById('open-service-list');
            const closeButton = document.getElementById('close-service-list');
            const currencyInputs = document.querySelectorAll('[data-currency-input]');

            const normalizeCurrencyValue = (value) => String(value ?? '').replace(/[^\d]/g, '');
            const formatCurrencyValue = (value) => {
                const digits = normalizeCurrencyValue(value);
                return digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            };

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            currencyInputs.forEach((input) => {
                input.value = formatCurrencyValue(input.value);
                input.addEventListener('input', () => {
                    input.value = formatCurrencyValue(input.value);
                });
            });

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', () => {
                    form.querySelectorAll('[data-currency-input]').forEach((input) => {
                        input.value = normalizeCurrencyValue(input.value);
                    });
                });
            });

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-service-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
