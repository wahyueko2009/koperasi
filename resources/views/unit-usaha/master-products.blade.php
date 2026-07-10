@extends('layouts.app')

@php
    $inactiveServicesCount = $servicesCount - $activeServicesCount;
    $inactiveInventoriesCount = $inventoriesCount - $activeInventoriesCount;
    $activeTab = old('master_tab', session('master_tab', request('tab', 'barang')));
    $isBarangTab = $activeTab !== 'jasa';
@endphp

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 pt-6">
                <div class="inline-flex rounded-[1.25rem] bg-slate-100 p-1">
                    <button
                        type="button"
                        data-master-tab-button="barang"
                        class="rounded-xl px-5 py-3 text-sm font-semibold transition {{ $isBarangTab ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                    >
                        Barang / Inventory
                    </button>
                    <button
                        type="button"
                        data-master-tab-button="jasa"
                        class="rounded-xl px-5 py-3 text-sm font-semibold transition {{ $isBarangTab ? 'text-slate-500 hover:text-slate-700' : 'bg-white text-slate-900 shadow-sm' }}"
                    >
                        Jasa / Service
                    </button>
                    <a
                        href="{{ route('unit-usaha.master.inventory-categories') }}"
                        class="rounded-xl px-5 py-3 text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                    >
                        Jenis Barang
                    </a>
                </div>
            </div>

            <div class="p-6">
                <div data-master-tab-panel="barang" class="{{ $isBarangTab ? '' : 'hidden' }}">
                    <div class="space-y-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Tab Barang</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Master Barang/Inventory</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Untuk item fisik yang punya jenis barang, harga beli, harga jual, stok, dan minimum stok.</p>
                                </div>
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                                    <i class="fas fa-boxes-stacked text-lg"></i>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('unit-usaha.master.inventory.store') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="master_tab" value="barang">

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Barang</label>
                                        <input
                                            name="code"
                                            value="{{ old('master_tab', 'barang') === 'barang' ? old('code') : '' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="INV-ATK-001"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Barang</label>
                                        <input
                                            name="name"
                                            value="{{ old('master_tab', 'barang') === 'barang' ? old('name') : '' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="Kertas A4"
                                        >
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Barang</label>
                                        <select name="category_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                            <option value="">Pilih jenis barang</option>
                                            @foreach ($inventoryCategories as $category)
                                                <option value="{{ $category->id }}" @selected(old('master_tab', 'barang') === 'barang' && (string) old('category_id') === (string) $category->id)>
                                                    {{ $category->code }} - {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-2 text-xs text-slate-500">Pilihan ini berasal dari master `Jenis Barang`, bukan diketik bebas di form barang.</p>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Satuan</label>
                                        <input
                                            name="unit"
                                            value="{{ old('master_tab', 'barang') === 'barang' ? old('unit', 'pcs') : 'pcs' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="pcs / rim / pack"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Minimum Stok</label>
                                    <input
                                        name="minimum_stock"
                                        value="{{ old('master_tab', 'barang') === 'barang' ? old('minimum_stock', 0) : 0 }}"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                        placeholder="10"
                                    >
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Beli</label>
                                        <input
                                            name="purchase_price"
                                            value="{{ old('master_tab', 'barang') === 'barang' ? old('purchase_price', 0) : 0 }}"
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
                                            value="{{ old('master_tab', 'barang') === 'barang' ? old('selling_price', 0) : 0 }}"
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
                                        placeholder="Catatan spesifikasi barang, merk, ukuran, atau penggunaan operasional."
                                    >{{ old('master_tab', 'barang') === 'barang' ? old('description') : '' }}</textarea>
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('master_tab', 'barang') === 'barang' ? (old('is_active', '1') ? 'checked' : '') : 'checked' }}>
                                    Barang aktif dan bisa dipakai dalam transaksi serta inventory
                                </label>

                                <div class="flex flex-wrap gap-3">
                                    <button class="flex-1 rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500">
                                        Simpan Barang/Inventory
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    id="open-master-barang-modal"
                                    class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800"
                                >
                                    View Data Barang
                                </button>
                            </form>
                    </div>
                </div>

                <div data-master-tab-panel="jasa" class="{{ $isBarangTab ? 'hidden' : '' }}">
                    <div class="space-y-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Tab Jasa</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Master Jasa/Service</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Untuk layanan yang dijual berdasarkan aktivitas kerja, bukan berdasarkan pergerakan stok fisik.</p>
                                </div>
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                                    <i class="fas fa-screwdriver-wrench text-lg"></i>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('unit-usaha.master.services.store') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="master_tab" value="jasa">

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Jasa</label>
                                        <input
                                            name="code"
                                            value="{{ old('master_tab') === 'jasa' ? old('code') : '' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="SRV-FC-A4"
                                        >
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Jasa</label>
                                        <input
                                            name="name"
                                            value="{{ old('master_tab') === 'jasa' ? old('name') : '' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="Fotokopi B/W A4"
                                        >
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jenis Jasa</label>
                                        <select name="category_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                            <option value="">Pilih jenis jasa</option>
                                            @foreach ($serviceCategories as $category)
                                                <option value="{{ $category->id }}" @selected(old('master_tab') === 'jasa' && (string) old('category_id') === (string) $category->id)>
                                                    {{ $category->code }} - {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">Satuan</label>
                                        <input
                                            name="unit"
                                            value="{{ old('master_tab') === 'jasa' ? old('unit', 'lembar') : 'lembar' }}"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                            placeholder="lembar / jasa / paket"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Jual</label>
                                    <input
                                        name="price"
                                        value="{{ old('master_tab') === 'jasa' ? old('price') : '' }}"
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
                                        placeholder="Catatan layanan, ukuran, warna, atau keterangan operasional lain."
                                    >{{ old('master_tab') === 'jasa' ? old('description') : '' }}</textarea>
                                </div>

                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('master_tab') === 'jasa' ? (old('is_active', '1') ? 'checked' : '') : 'checked' }}>
                                    Jasa aktif dan bisa dipilih di kasir
                                </label>

                                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                                    Simpan Jasa/Service
                                </button>
                                <button
                                    type="button"
                                    id="open-master-jasa-modal"
                                    class="w-full rounded-2xl bg-amber-500 px-4 py-3 font-semibold text-white hover:bg-amber-400"
                                >
                                    View Data Jasa
                                </button>
                            </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div id="master-barang-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-master-barang-modal></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="sticky top-0 z-20 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Data Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Barang / Inventory</h3>
                        <p class="mt-2 text-sm text-slate-500">Klik salah satu baris untuk menampilkan form perubahan harga di bawah tabel.</p>
                    </div>
                    <button
                        type="button"
                        id="close-master-barang-modal"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                        aria-label="Tutup popup data barang"
                    >
                        <i class="fas fa-xmark text-base"></i>
                    </button>
                </div>

                <div class="master-barang-scroll-area space-y-6 overflow-y-auto px-6 py-6">
                    <div id="barang-list-section" class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                            <p class="text-sm font-semibold text-slate-800">Klik baris barang untuk ubah harga</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Nama Barang</th>
                                        <th class="px-5 py-3">Jenis</th>
                                        <th class="px-5 py-3">Stok</th>
                                        <th class="px-5 py-3">Harga Beli</th>
                                        <th class="px-5 py-3">Harga Jual</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($inventoryItems as $inventory)
                                        <tr
                                            class="cursor-pointer transition hover:bg-sky-50"
                                            data-price-row
                                            data-id="{{ $inventory->id }}"
                                            data-name="{{ $inventory->name }}"
                                            data-code="{{ $inventory->code }}"
                                            data-purchase-price="{{ (float) $inventory->purchase_price }}"
                                            data-selling-price="{{ (float) $inventory->selling_price }}"
                                        >
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->code }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->name }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $inventory->categoryLabel() ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ number_format((int) $inventory->stock) }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $inventory->purchase_price, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $inventory->selling_price, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada barang tersimpan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <section id="price-editor-panel" class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5 {{ old('price_inventory_id') ? '' : 'hidden' }}">
                        <div class="border-b border-slate-200 pb-4">
                            <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Perubahan Harga</p>
                            <h4 class="mt-2 text-lg font-bold text-slate-900">Ubah harga barang terpilih</h4>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                <span id="selected-price-item-label">
                                    @if (old('price_inventory_id'))
                                        Barang terpilih siap diubah harganya.
                                    @else
                                        Klik baris barang di atas untuk memilih data yang ingin diperbaiki.
                                    @endif
                                </span>
                            </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('unit-usaha.master.inventory.prices.update') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="master_tab" value="barang">
                            <input type="hidden" id="price_inventory_id" name="price_inventory_id" value="{{ old('price_inventory_id') }}">

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Beli Baru</label>
                                    <input
                                        id="new_purchase_price"
                                        name="new_purchase_price"
                                        value="{{ old('new_purchase_price', 0) }}"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        data-currency-input
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                    >
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Harga Jual Baru</label>
                                    <input
                                        id="new_selling_price"
                                        name="new_selling_price"
                                        value="{{ old('new_selling_price', 0) }}"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        data-currency-input
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Perubahan</label>
                                <textarea
                                    name="change_notes"
                                    rows="3"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                    placeholder="Contoh: harga supplier naik per Juni, penyesuaian margin toko, dll."
                                >{{ old('change_notes') }}</textarea>
                            </div>

                            <button class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 hover:bg-slate-100">
                                Simpan Perubahan Harga
                            </button>
                        </form>
                    </section>

                    <section class="rounded-[1.5rem] border border-slate-200 bg-white px-5 py-5">
                        <div class="border-b border-slate-100 pb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Histori Harga</p>
                            <h4 class="mt-2 text-lg font-bold text-slate-900">Perubahan harga barang terpilih</h4>
                            <p id="selected-history-label" class="mt-2 text-sm text-slate-500">Klik baris barang di atas untuk melihat histori harga item tersebut.</p>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-[1.25rem] border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                                            <th class="px-4 py-3">Jenis Harga</th>
                                            <th class="px-4 py-3">Perubahan</th>
                                            <th class="px-4 py-3">Sumber</th>
                                            <th class="px-4 py-3">Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selected-history-table-body" class="divide-y divide-slate-100 bg-white">
                                        @foreach ($recentPriceHistories as $history)
                                            <tr class="hidden" data-history-row data-inventory-id="{{ $history->inventory_id }}">
                                                <td class="px-4 py-3 text-sm text-slate-700">{{ $history->price_type === 'purchase' ? 'Harga Beli' : 'Harga Jual' }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-700">
                                                    {{ $history->old_price !== null ? 'Rp ' . number_format((float) $history->old_price, 0, ',', '.') : 'Awal' }}
                                                    ->
                                                    Rp {{ number_format((float) $history->new_price, 0, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-slate-600">{{ str_replace('_', ' ', ucfirst($history->change_source)) }}</td>
                                                <td class="px-4 py-3 text-sm text-slate-600">{{ optional($history->effective_at)->format('d M Y H:i') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr id="selected-history-empty-row">
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Pilih barang terlebih dulu untuk melihat histori harga.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

            </section>
        </div>
    </div>

    <div id="master-jasa-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-master-jasa-modal></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="sticky top-0 z-20 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Data Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Jasa / Service</h3>
                    </div>
                    <button
                        type="button"
                        id="close-master-jasa-modal"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                        aria-label="Tutup popup data jasa"
                    >
                        <i class="fas fa-xmark text-base"></i>
                    </button>
                </div>

                <div class="overflow-y-auto px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Kode</th>
                                        <th class="px-5 py-3">Nama Jasa</th>
                                        <th class="px-5 py-3">Kategori</th>
                                        <th class="px-5 py-3">Satuan</th>
                                        <th class="px-5 py-3">Harga</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($serviceItems as $service)
                                        <tr>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $service->code }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $service->name }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $service->category }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $service->unit }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $service->price, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada jasa tersimpan.</td>
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
    <style>
        .master-barang-scroll-area {
            max-height: calc(90vh - 92px);
            padding-right: 0.75rem;
            scrollbar-width: auto;
            scrollbar-color: #64748b #e5e7eb;
        }

        .master-barang-scroll-area::-webkit-scrollbar {
            width: 16px;
        }

        .master-barang-scroll-area::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 9999px;
        }

        .master-barang-scroll-area::-webkit-scrollbar-thumb {
            background: #6b7280;
            border-radius: 9999px;
            border: 3px solid #e5e7eb;
        }

        .master-barang-scroll-area::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }
    </style>

    <script>
        (() => {
            const buttons = document.querySelectorAll('[data-master-tab-button]');
            const panels = document.querySelectorAll('[data-master-tab-panel]');
            const barangModal = document.getElementById('master-barang-modal');
            const jasaModal = document.getElementById('master-jasa-modal');
            const priceEditorPanel = document.getElementById('price-editor-panel');
            const barangListSection = document.getElementById('barang-list-section');
            const priceInventoryId = document.getElementById('price_inventory_id');
            const newPurchasePrice = document.getElementById('new_purchase_price');
            const newSellingPrice = document.getElementById('new_selling_price');
            const selectedPriceItemLabel = document.getElementById('selected-price-item-label');
            const selectedHistoryLabel = document.getElementById('selected-history-label');
            const historyRows = document.querySelectorAll('[data-history-row]');
            const historyEmptyRow = document.getElementById('selected-history-empty-row');
            const currencyInputs = document.querySelectorAll('[data-currency-input]');

            const normalizeCurrencyValue = (value) => String(value ?? '').replace(/[^\d]/g, '');
            const formatCurrencyValue = (value) => {
                const digits = normalizeCurrencyValue(value);
                return digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
            };

            const setActiveTab = (tab) => {
                buttons.forEach((button) => {
                    const isActive = button.dataset.masterTabButton === tab;
                    button.classList.toggle('bg-white', isActive);
                    button.classList.toggle('text-slate-900', isActive);
                    button.classList.toggle('shadow-sm', isActive);
                    button.classList.toggle('text-slate-500', !isActive);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.masterTabPanel !== tab);
                });
            };

            const toggleModal = (modal, show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            const updateHistoryRows = (inventoryId, inventoryName, inventoryCode) => {
                let visibleCount = 0;

                historyRows.forEach((row) => {
                    const show = row.dataset.inventoryId === String(inventoryId);
                    row.classList.toggle('hidden', !show);
                    if (show) visibleCount += 1;
                });

                if (selectedHistoryLabel) {
                    selectedHistoryLabel.textContent = `Histori harga untuk: ${inventoryName || '-'} (${inventoryCode || '-'})`;
                }

                if (historyEmptyRow) {
                    historyEmptyRow.classList.toggle('hidden', visibleCount > 0);
                    historyEmptyRow.querySelector('td').textContent = visibleCount > 0
                        ? ''
                        : `Belum ada histori harga untuk ${inventoryName || 'barang ini'}.`;
                }
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

            document.getElementById('open-master-barang-modal')?.addEventListener('click', () => toggleModal(barangModal, true));
            document.getElementById('close-master-barang-modal')?.addEventListener('click', () => toggleModal(barangModal, false));
            barangModal?.querySelectorAll('[data-close-master-barang-modal]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(barangModal, false));
            });

            document.getElementById('open-master-jasa-modal')?.addEventListener('click', () => toggleModal(jasaModal, true));
            document.getElementById('close-master-jasa-modal')?.addEventListener('click', () => toggleModal(jasaModal, false));
            jasaModal?.querySelectorAll('[data-close-master-jasa-modal]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(jasaModal, false));
            });

            document.querySelectorAll('[data-price-row]').forEach((row) => {
                row.addEventListener('click', () => {
                    document.querySelectorAll('[data-price-row]').forEach((item) => {
                        item.classList.remove('bg-sky-50', 'ring-1', 'ring-sky-200');
                    });
                    row.classList.add('bg-sky-50', 'ring-1', 'ring-sky-200');
                    priceEditorPanel?.classList.remove('hidden');
                    if (priceInventoryId) priceInventoryId.value = row.dataset.id || '';
                    if (newPurchasePrice) newPurchasePrice.value = formatCurrencyValue(row.dataset.purchasePrice || 0);
                    if (newSellingPrice) newSellingPrice.value = formatCurrencyValue(row.dataset.sellingPrice || 0);
                    if (selectedPriceItemLabel) {
                        selectedPriceItemLabel.textContent = `Barang terpilih: ${row.dataset.name || '-'} (${row.dataset.code || '-'})`;
                    }
                    updateHistoryRows(row.dataset.id || '', row.dataset.name || '', row.dataset.code || '');
                    priceEditorPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            buttons.forEach((button) => {
                button.addEventListener('click', () => setActiveTab(button.dataset.masterTabButton));
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') return;
                toggleModal(barangModal, false);
                toggleModal(jasaModal, false);
            });

            if (priceInventoryId?.value) {
                toggleModal(barangModal, true);
                const selectedRow = document.querySelector(`[data-price-row][data-id="${priceInventoryId.value}"]`);
                if (selectedRow) {
                    selectedRow.classList.add('bg-sky-50', 'ring-1', 'ring-sky-200');
                    updateHistoryRows(
                        selectedRow.dataset.id || '',
                        selectedRow.dataset.name || '',
                        selectedRow.dataset.code || ''
                    );
                }
            }
        })();
    </script>
@endpush
