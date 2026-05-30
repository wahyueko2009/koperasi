@extends('layouts.app')

@php
    $totalPurchase = $purchases->sum('total_amount');
    $totalSales = $sales->sum('total_amount');
    $estimatedMargin = $totalSales - $totalPurchase;
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Penjualan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalSales, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $sales->count() }} transaksi kasir tercatat.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Pembelian</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalPurchase, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $purchases->count() }} transaksi barang masuk tercatat.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Estimasi Selisih</p>
                <p class="mt-3 text-3xl font-bold {{ $estimatedMargin >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                    Rp {{ number_format((float) $estimatedMargin, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-sm text-slate-500">Selisih kasar penjualan terhadap pembelian.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Stock Opname</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stockOpnames->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $adjustedItemCount }} item pernah disesuaikan.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Penjualan Terbaru</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Rekap Kasir</h3>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-5 py-3">Nomor</th>
                                    <th class="px-5 py-3">Tanggal</th>
                                    <th class="px-5 py-3">Item</th>
                                    <th class="px-5 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($sales->take(8) as $sale)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $sale->sale_number }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ optional($sale->sale_date)->format('d M Y') }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ $sale->items->count() }} item</td>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada penjualan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pembelian Terbaru</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Rekap Barang Masuk</h3>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-5 py-3">Nomor</th>
                                    <th class="px-5 py-3">Tanggal</th>
                                    <th class="px-5 py-3">Supplier</th>
                                    <th class="px-5 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($purchases->take(8) as $purchase)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">{{ $purchase->purchase_number }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ optional($purchase->purchase_date)->format('d M Y') }}</td>
                                        <td class="px-5 py-4 text-sm text-slate-700">{{ $purchase->supplier_name ?: '-' }}</td>
                                        <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pembelian.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
