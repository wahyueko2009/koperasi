@extends('layouts.app')

@php
    $todayPurchaseTotal = $todayPurchases->sum('total_amount');
    $todaySalesTotal = $todaySales->sum('total_amount');
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Omzet Hari Ini</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $todaySalesTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $todaySales->count() }} transaksi kasir tercatat hari ini.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pembelian Hari Ini</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $todayPurchaseTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $todayPurchases->count() }} transaksi barang masuk hari ini.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Master Barang Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeInventoryCount }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $lowStockItems->count() }} item sudah menyentuh batas minimum.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Master Jasa Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeServiceCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Siap dipakai untuk transaksi kasir.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Perhatian Stok</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Barang Menipis</h3>
                    </div>
                    <span class="rounded-full bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700">
                        {{ $lowStockItems->count() }} item
                    </span>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($lowStockItems as $inventory)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $inventory->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $inventory->category }} · {{ $inventory->code }}</p>
                                </div>
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                    Minimum {{ $inventory->minimum_stock }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-slate-500">Stok saat ini</span>
                                <span class="font-semibold {{ $inventory->stock <= $inventory->minimum_stock ? 'text-rose-600' : 'text-slate-900' }}">
                                    {{ number_format((int) $inventory->stock) }} {{ $inventory->unit }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Semua stok barang masih aman di atas batas minimum.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Aktivitas Terbaru</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Transaksi Terakhir</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($recentSales as $sale)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $sale->sale_number }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ optional($sale->sale_date)->format('d M Y') }} · {{ $sale->items->count() }} item</p>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">{{ $sale->items->pluck('item_name')->take(3)->implode(', ') }}</p>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada transaksi kasir yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
