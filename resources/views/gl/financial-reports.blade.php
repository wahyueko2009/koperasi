@extends('layouts.app')

@php
    $typeLabels = ['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Modal', 'income' => 'Pendapatan', 'expense' => 'Beban'];
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('gl.financial-reports') }}" class="grid gap-4 md:grid-cols-[1fr_0.35fr]">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Per Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div class="flex items-end">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Tampilkan Laporan</button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Pendapatan</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">Rp {{ number_format((float) $incomeTotal, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-rose-100 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Beban</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">Rp {{ number_format((float) $expenseTotal, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-sky-100 bg-sky-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Laba Bersih</p>
                <p class="mt-2 text-2xl font-bold text-sky-900">Rp {{ number_format((float) $netIncome, 0, ',', '.') }}</p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            @foreach (['income' => $incomes, 'expense' => $expenses, 'asset' => $assets, 'liability' => $liabilities, 'equity' => $equities] as $type => $items)
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $typeLabels[$type] }}</p>
                    <div class="mt-5 space-y-3">
                        @forelse ($items as $item)
                            <div class="flex items-center justify-between gap-3 rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $item->code }} - {{ $item->name }}</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $item->net_balance, 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <p class="rounded-[1.25rem] bg-slate-50 px-4 py-6 text-sm text-slate-500">Belum ada data {{ strtolower($typeLabels[$type]) }}.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </section>
    </div>
@endsection
