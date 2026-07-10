@extends('layouts.app')

@php
    $title = 'Ringkasan Simpan Pinjam - Koperasi Digital Mandiri';
    $sectionLabel = 'Operasional Koperasi';
    $pageTitle = 'Simpan Pinjam';
    $pageDescription = 'Halaman ringkasan untuk melihat angka utama dan menjalankan transaksi inti tanpa membuat layar terasa padat.';
@endphp

@section('content')
    <div class="space-y-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ringkasan Operasional</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Angka utama Simpan Pinjam</h2>
                </div>
                <p class="max-w-xl text-sm leading-6 text-slate-500">
                    Halaman ini dipakai untuk melihat kondisi singkat hari ini dan menjalankan transaksi inti. Daftar detailnya dipindah ke submenu agar tampilan tetap ringan.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-500 text-white shadow-lg shadow-teal-200">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Simpanan</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($savingLoanStats['total_savings'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-sm text-teal-600">Akumulasi seluruh simpanan anggota</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sky-500 text-white shadow-lg shadow-sky-200">
                            <i class="fas fa-hand-holding-usd text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Outstanding Pinjaman</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($savingLoanStats['active_loans'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-sm text-sky-600">{{ $activeLoans->count() }} pinjaman masih berjalan</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500 text-white shadow-lg shadow-amber-200">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Angsuran Hari Ini</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($savingLoanStats['today_installments'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-sm text-amber-600">{{ $recentPayments->where('payment_date', today())->count() }} pembayaran tercatat</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Anggota Aktif</p>
                            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($savingLoanStats['active_members']) }}</p>
                            <p class="mt-2 text-sm text-emerald-600">Siap dipakai untuk transaksi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Aksi Cepat</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Transaksi Utama</h2>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
            <button type="button" data-open-transaction-modal="saving" class="group flex items-center justify-between rounded-[1.75rem] border border-slate-200 bg-white px-6 py-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-600">
                        <i class="fas fa-download text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-semibold text-slate-900">Setoran Simpanan</p>
                        <p class="mt-1 text-sm text-slate-500">Catat setoran simpanan anggota aktif.</p>
                    </div>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition group-hover:border-teal-200 group-hover:text-teal-600">
                    <i class="fas fa-chevron-right"></i>
                </span>
            </button>

            <button type="button" data-open-transaction-modal="loan" class="group flex items-center justify-between rounded-[1.75rem] border border-slate-200 bg-white px-6 py-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sky-50 text-sky-600">
                        <i class="fas fa-file-signature text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-semibold text-slate-900">Pengajuan Pinjaman</p>
                        <p class="mt-1 text-sm text-slate-500">Buat pinjaman baru untuk anggota aktif.</p>
                    </div>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition group-hover:border-sky-200 group-hover:text-sky-600">
                    <i class="fas fa-chevron-right"></i>
                </span>
            </button>

            <button type="button" data-open-transaction-modal="payment" class="group flex items-center justify-between rounded-[1.75rem] border border-slate-200 bg-white px-6 py-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <i class="fas fa-money-check-dollar text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-semibold text-slate-900">Pembayaran Pinjaman</p>
                        <p class="mt-1 text-sm text-slate-500">Catat angsuran dari pinjaman yang masih aktif.</p>
                    </div>
                </div>
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-400 transition group-hover:border-emerald-200 group-hover:text-emerald-600">
                    <i class="fas fa-chevron-right"></i>
                </span>
            </button>
            </div>
        </div>

        <div class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Area Kerja Berikutnya</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-900">Pilih Area Operasional</h2>
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <a href="{{ route('simpan-pinjam.loans.monitoring') }}" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-6 py-6 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md">
                    <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pinjaman</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Monitoring dan Approval</h2>
                        <p class="mt-2 text-sm text-slate-500">Masuk ke area pinjaman untuk memantau pengajuan, approval, pencairan, dan progres pinjaman.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                    </div>
                </a>

                <a href="{{ route('simpan-pinjam.loans.konkes') }}" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-6 py-6 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-md">
                    <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">KONKES</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Laporan Kualitas Pinjaman</h2>
                        <p class="mt-2 text-sm text-slate-500">Masuk ke laporan KONKES untuk melihat status lancar, DPK, dan macet berdasarkan tunggakan angsuran.</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-white">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    @include('partials.saving-loan-transaction-modals')
@endsection

@push('scripts')
    @include('partials.saving-loan-transaction-scripts')
@endpush
