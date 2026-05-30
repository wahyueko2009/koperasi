@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="border-b border-slate-100 pb-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Rencana Modul</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Proses Depresiasi</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Halaman ini disiapkan untuk menghitung penyusutan aset per bulan, memantau akumulasi depresiasi, dan menyiapkan posting jurnal ke akuntansi.</p>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Periode</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Depresiasi Bulanan</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Menentukan periode penyusutan dan aset yang masuk dalam proses perhitungan.</p>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Perhitungan</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Akumulasi dan Nilai Buku</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Menghasilkan beban depresiasi, akumulasi depresiasi, dan nilai buku tiap aset.</p>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Posting</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Jurnal Otomatis</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Menyiapkan jurnal beban depresiasi dan akumulasi depresiasi untuk periode yang dipilih.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status Halaman</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Siap Untuk Workflow Depresiasi</h3>
                </div>
                <div class="rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
                    Menunggu detail proses
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Periode Buku</p>
                    <p class="mt-2 text-sm text-slate-500">Bulan dan tahun proses depresiasi.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Generate</p>
                    <p class="mt-2 text-sm text-slate-500">Hitung otomatis beban depresiasi per aset.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Review</p>
                    <p class="mt-2 text-sm text-slate-500">Validasi hasil sebelum diposting ke jurnal.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Posting</p>
                    <p class="mt-2 text-sm text-slate-500">Simpan hasil depresiasi sebagai transaksi resmi.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
