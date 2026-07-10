@extends('layouts.app')

@php
    $activeItems = $inventories->where('is_active', true)->count();
    $inactiveItems = $inventories->where('is_active', false)->count();
    $categoriesCount = $inventoryCategories->count();
    $lowStockItems = $inventories->filter(fn ($item) => $item->stock <= $item->minimum_stock)->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-5xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Form Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Tambah Inventory/ATK</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Masukkan data induk barang inventory, ATK, atau bahan baku. Stok barang akan bergerak dari transaksi pembelian, penjualan, dan stock opname.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('unit-usaha.master.inventory.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Barang</label>
                            <input
                                name="code"
                                value="{{ old('code') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="INV-ATK-001"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Barang</label>
                            <input
                                name="name"
                                value="{{ old('name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Kertas A4"
                            >
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Barang</label>
                            <select
                                name="category_id"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            >
                                <option value="">Pilih jenis barang</option>
                                @foreach ($inventoryCategories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                        {{ $category->name }} ({{ $category->code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Kelola daftar jenis dari tab `Jenis Barang` agar klasifikasi inventory konsisten.</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Satuan</label>
                            <input
                                name="unit"
                                value="{{ old('unit', 'pcs') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="pcs / rim / botol / pack"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Minimum Stok</label>
                        <input
                            name="minimum_stock"
                            value="{{ old('minimum_stock', 0) }}"
                            type="number"
                            min="0"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="10"
                        >
                        <p class="mt-2 text-xs text-slate-500">Nilai ini dipakai sebagai batas minimum untuk monitoring stok menipis.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Beli</label>
                            <input
                                name="purchase_price"
                                value="{{ old('purchase_price', 0) }}"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                data-currency-input
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="0"
                            >
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Jual</label>
                            <input
                                name="selling_price"
                                value="{{ old('selling_price', 0) }}"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                data-currency-input
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="0"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            name="description"
                            rows="4"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            placeholder="Catatan spesifikasi barang, ukuran, merk, atau penggunaan operasional."
                        >{{ old('description') }}</textarea>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', '1') ? 'checked' : '' }}>
                        Barang aktif dan bisa dipakai dalam transaksi serta inventory
                    </label>

                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                        Simpan Inventory/ATK
                    </button>
                    <button
                        type="button"
                        id="open-master-inventory-list"
                        class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                    >
                        View Data
                    </button>
                </form>
            </section>
        </div>
    </div>

    <div id="master-inventory-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-master-inventory-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Inventory/ATK</h3>
                    </div>
                    <button
                        type="button"
                        id="close-master-inventory-list"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup data inventory"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ $inventories->count() }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Aktif</p>
                            <p class="mt-2 text-xl font-bold text-emerald-800">{{ $activeItems }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Kategori</p>
                            <p class="mt-2 text-xl font-bold text-amber-800">{{ $categoriesCount }}</p>
                        </div>
                        <div class="rounded-[1.25rem] bg-rose-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Stok Minimum</p>
                            <p class="mt-2 text-xl font-bold text-rose-800">{{ $lowStockItems }}</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Nama Barang</th>
                                        <th class="px-5 py-3">Kategori</th>
                                        <th class="px-5 py-3">Satuan</th>
                                        <th class="px-5 py-3">Stok</th>
                                        <th class="px-5 py-3">Deskripsi</th>
                                        <th class="px-5 py-3">Harga</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($inventories as $inventory)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->code }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->name }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $inventory->categoryLabel() ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $inventory->unit }}</td>
                                            <td class="px-5 py-4 text-sm {{ $inventory->stock <= $inventory->minimum_stock ? 'font-semibold text-rose-600' : 'text-slate-700' }}">{{ number_format((int) $inventory->stock) }} / min {{ number_format((int) $inventory->minimum_stock) }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-600">{{ $inventory->description ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">Beli Rp {{ number_format((float) $inventory->purchase_price, 0, ',', '.') }} | Jual Rp {{ number_format((float) $inventory->selling_price, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $inventory->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $inventory->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada inventory/ATK yang tersimpan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-[1.25rem] bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-600">
                        <p class="font-semibold text-slate-900">Cara baca stok</p>
                        <p class="mt-2">Stok di master adalah posisi stok saat ini. Penambahan stok berasal dari `Pembelian / Barang Masuk`, pengurangan stok berasal dari `Kasir / POS`, dan nanti penyesuaian fisik dilakukan dari `Stock Opname`.</p>
                    </div>

                    @if ($inactiveItems > 0)
                        <p class="text-sm text-slate-500">Item nonaktif: {{ $inactiveItems }} data. Item nonaktif tetap tersimpan untuk referensi historis, tetapi sebaiknya tidak dipakai pada transaksi baru.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('master-inventory-list-modal');
            const openButton = document.getElementById('open-master-inventory-list');
            const closeButton = document.getElementById('close-master-inventory-list');
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
            modal?.querySelectorAll('[data-close-master-inventory-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
