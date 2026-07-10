@extends('layouts.app')

@php
    $title = 'Laporan KONKES - Simpan Pinjam';
    $sectionLabel = 'Operasional Koperasi';
    $pageTitle = 'Laporan KONKES';
    $pageDescription = 'Pantau kualitas pinjaman anggota dengan klasifikasi Lancar, DPK, dan Macet berdasarkan hari tunggakan.';
@endphp

@section('content')
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-5 border-b border-slate-100 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Kondisi Kesehatan Pinjaman</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Laporan KONKES Simpan Pinjam</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Klasifikasi memakai metode 3 kategori: Lancar untuk tunggakan sampai 30 hari, DPK untuk 31-90 hari, dan Macet untuk lebih dari 90 hari.
                    </p>
                </div>
                <form method="GET" action="{{ route('simpan-pinjam.loans.konkes') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label for="report_date" class="mb-2 block text-sm font-semibold text-slate-700">Tanggal laporan</label>
                        <input
                            type="date"
                            id="report_date"
                            name="report_date"
                            value="{{ $reportDate->toDateString() }}"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
                        >
                    </div>
                    <button class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Tampilkan
                    </button>
                </form>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Per Tanggal</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $reportDate->translatedFormat('d M Y') }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ number_format($konkesSummary['total_loans']) }} pinjaman dianalisis</p>
                </div>
                <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Lancar</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-900">{{ number_format($konkesSummary['lancar']['count']) }}</p>
                    <p class="mt-2 text-sm text-emerald-700">Rp {{ number_format($konkesSummary['lancar']['outstanding'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-emerald-700">{{ number_format($konkesSummary['lancar']['ratio'], 1, ',', '.') }}% dari outstanding</p>
                </div>
                <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">DPK</p>
                    <p class="mt-2 text-2xl font-bold text-amber-900">{{ number_format($konkesSummary['dpk']['count']) }}</p>
                    <p class="mt-2 text-sm text-amber-700">Rp {{ number_format($konkesSummary['dpk']['outstanding'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-amber-700">{{ number_format($konkesSummary['dpk']['ratio'], 1, ',', '.') }}% dari outstanding</p>
                </div>
                <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">Macet</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900">{{ number_format($konkesSummary['macet']['count']) }}</p>
                    <p class="mt-2 text-sm text-rose-700">Rp {{ number_format($konkesSummary['macet']['outstanding'], 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-rose-700">{{ number_format($konkesSummary['macet']['ratio'], 1, ',', '.') }}% dari outstanding</p>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Tunggakan</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($konkesSummary['total_arrears'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm text-slate-500">Outstanding Rp {{ number_format($konkesSummary['total_outstanding'], 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Pinjaman</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Daftar KONKES per Pinjaman</h2>
                <p class="mt-2 text-sm text-slate-500">Perhitungan tunggakan menggunakan jadwal cicilan bulanan sejak tanggal pencairan dan dibandingkan dengan total angsuran yang sudah tercatat.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                            <th class="px-6 py-4">Pinjaman</th>
                            <th class="px-6 py-4">Anggota</th>
                            <th class="px-6 py-4">Pencairan</th>
                            <th class="px-6 py-4 text-right">Outstanding</th>
                            <th class="px-6 py-4 text-right">Tunggakan</th>
                            <th class="px-6 py-4 text-center">Hari</th>
                            <th class="px-6 py-4 text-center">Kolektibilitas</th>
                            <th class="px-6 py-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($konkesLoans as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ $row['loan']->application_number ?: 'PJN-' . str_pad((string) $row['loan']->id, 6, '0', STR_PAD_LEFT) }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $row['loan']->loan_type_label }} • {{ $row['loan']->tenor_months }} bulan</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ $row['member']->name ?? '-' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $row['member']->nik ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ optional($row['disbursement_date'])->format('d M Y') ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900">Rp {{ number_format((float) $row['loan']->remaining_balance, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <p class="text-sm font-semibold text-slate-900">Rp {{ number_format($row['arrears_amount'], 0, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ number_format($row['installments_in_arrears']) }} angsuran</p>
                                </td>
                                <td class="px-6 py-4 text-center text-sm font-semibold text-slate-900">{{ number_format($row['days_overdue']) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['quality']['class'] }}">
                                        {{ $row['quality']['label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <p>{{ $row['quality']['description'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        @if ($row['oldest_unpaid_due_date'])
                                            JT tertua {{ $row['oldest_unpaid_due_date']->format('d M Y') }}
                                        @else
                                            Pembayaran masih dalam batas lancar
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">Belum ada pinjaman yang bisa dihitung untuk laporan KONKES.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
