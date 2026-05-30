@extends('layouts.app')

@php
    $totalStockValue = $inventories->sum(fn ($item) => (float) $item->stock * (float) $item->purchase_price);
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Item</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $inventories->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Seluruh master barang inventory aktif dan nonaktif.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Barang Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeInventoryCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Siap dipakai untuk pembelian dan transaksi kasir.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Stok Menipis</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">{{ $lowStockItems->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Perlu segera dicek atau dilakukan pembelian.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Stok</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalStockValue, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Estimasi berdasarkan harga beli terakhir di master.</p>
            </div>
        </section>

        <div class="mx-auto max-w-6xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar Stok</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Inventory Saat Ini</h3>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-5 py-3">Kode</th>
                                    <th class="px-5 py-3">Barang</th>
                                    <th class="px-5 py-3">Kategori</th>
                                    <th class="px-5 py-3">Satuan</th>
                                    <th class="px-5 py-3">Stok</th>
                                    <th class="px-5 py-3">Harga Beli</th>
                                    <th class="px-5 py-3">Harga Jual</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($inventories as $inventory)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->code }}</td>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $inventory->name }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ $inventory->category }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ $inventory->unit }}</td>
                                        <td class="px-5 py-4 text-sm {{ $inventory->stock <= $inventory->minimum_stock ? 'font-semibold text-rose-600' : 'text-slate-700' }}">{{ number_format((int) $inventory->stock) }} / min {{ number_format((int) $inventory->minimum_stock) }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $inventory->purchase_price, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $inventory->selling_price, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $inventory->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $inventory->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data inventory.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <button
                    type="button"
                    id="open-inventory-monitoring"
                    class="mt-6 w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white transition hover:bg-sky-500"
                >
                    View Data
                </button>
            </section>
        </div>
    </div>

    <div id="inventory-monitoring-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-inventory-monitoring></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Monitoring</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Fokus Tindak Lanjut</h3>
                    </div>
                    <button
                        type="button"
                        id="close-inventory-monitoring"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800"
                        aria-label="Tutup popup monitoring"
                    >
                        X
                    </button>
                </div>

                <div class="space-y-3 overflow-y-auto px-6 py-6">
                    @forelse ($lowStockItems as $inventory)
                        <div class="rounded-[1.5rem] border border-rose-100 bg-rose-50/60 px-5 py-4">
                            <p class="font-semibold text-slate-900">{{ $inventory->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $inventory->category }}</p>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-slate-500">Sisa stok</span>
                                <span class="font-semibold text-rose-700">{{ number_format((int) $inventory->stock) }} {{ $inventory->unit }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Tidak ada barang yang berada di bawah batas minimum.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('inventory-monitoring-modal');
            const openButton = document.getElementById('open-inventory-monitoring');
            const closeButton = document.getElementById('close-inventory-monitoring');

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-inventory-monitoring]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
