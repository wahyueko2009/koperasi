@extends('layouts.app')

@php
    $totalAcquisition = $assets->sum(fn ($asset) => (float) $asset->acquisition_value);
    $totalBookValue = $assets->sum(fn ($asset) => (float) $asset->book_value);
    $periodDepreciationTotal = $periodDepreciations->sum(fn ($item) => (float) $item->amount);
    $disposedValue = $disposals->sum(fn ($item) => (float) $item->disposal_value);
@endphp

@section('content')
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Filter Periode</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Laporan Fixed Aset</h3>
                    <p class="mt-2 text-sm text-slate-500">Periode aktif untuk rekap depresiasi dan disposal: {{ $periodEnd->translatedFormat('F Y') }}.</p>
                </div>

                <div class="flex flex-col gap-3 md:flex-row md:items-end">
                    <form method="GET" action="{{ route('fixed-assets.reports') }}" class="grid gap-3 md:grid-cols-3">
                        <select name="month" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ sprintf('%02d', $month) }}</option>
                            @endforeach
                        </select>
                        <input name="year" type="number" min="2020" max="2100" value="{{ $selectedYear }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan</button>
                    </form>
                    <a href="{{ route('fixed-assets.reports.export.excel', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        Download Excel
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Register Aset</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($assets->count()) }}</p>
                <p class="mt-2 text-sm text-slate-500">Total aset yang tercatat di modul FA.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Perolehan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format($totalAcquisition, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi nilai awal seluruh fixed aset.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Buku</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">Rp {{ number_format($totalBookValue, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Posisi nilai buku setelah depresiasi.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Depresiasi Periode</p>
                <p class="mt-3 text-3xl font-bold text-cyan-700">Rp {{ number_format($periodDepreciationTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $periodDepreciations->count() }} baris depresiasi periode ini.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ringkasan Kategori</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Komposisi Register Aset</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($categorySummary as $row)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['category'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $row['count'] }} aset</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">Perolehan Rp {{ number_format((float) $row['acquisition_value'], 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Nilai buku Rp {{ number_format((float) $row['book_value'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada kategori aset yang bisa direkap.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Perhatian Aset</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Mendekati Akhir Umur Manfaat</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($nearEndOfLife as $asset)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $asset->asset_code }} - {{ $asset->asset_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $asset->category }}{{ $asset->costCenter ? ' · ' . $asset->costCenter->name : '' }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ (int) $asset->remaining_months <= 3 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                    Sisa {{ (int) $asset->remaining_months }} bulan
                                </span>
                            </div>
                            <div class="mt-3 text-sm text-slate-600">
                                Nilai buku: Rp {{ number_format((float) $asset->book_value, 0, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada aset yang mendekati akhir umur manfaat dalam 12 bulan.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="border-b border-slate-100 pb-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Rekap Depresiasi</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-900">Penyusutan Periode {{ sprintf('%02d/%04d', $selectedMonth, $selectedYear) }}</h3>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Aset</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Jumlah</th>
                                <th class="px-5 py-3">Akumulasi</th>
                                <th class="px-5 py-3">Nilai Buku</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($periodDepreciations as $item)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $item->asset?->asset_code }} - {{ $item->asset?->asset_name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $item->asset?->category }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ optional($item->depreciation_date)->format('d M Y') }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $item->accumulated_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $item->book_value, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $item->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ strtoupper($item->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada depresiasi pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Disposal Periode</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Penghentian / Pelepasan Aset</h3>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                    Nilai pelepasan Rp {{ number_format($disposedValue, 0, ',', '.') }}
                </span>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Aset</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Nilai Disposal</th>
                                <th class="px-5 py-3">Referensi Jurnal</th>
                                <th class="px-5 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($disposals as $item)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $item->asset?->asset_code }} - {{ $item->asset?->asset_name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $item->asset?->category }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ optional($item->mutation_date)->format('d M Y') }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $item->disposal_value, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $item->disposal_reference_number ?: '-' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $item->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada disposal aset pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
