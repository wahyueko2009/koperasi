@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="border-b border-slate-100 pb-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Rencana Modul</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Master Fixed Aset</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Halaman ini disiapkan untuk pencatatan aset tetap koperasi, mulai dari data perolehan, umur manfaat, nilai residu, hingga akun akuntansi yang terkait.</p>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Data Utama</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Identitas Aset</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Kode aset, nama aset, kategori, lokasi, tanggal perolehan, dan status aset.</p>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nilai Perolehan</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Nilai dan Umur Manfaat</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Harga perolehan, nilai residu, umur manfaat, metode penyusutan, dan nilai buku.</p>
                </div>
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Integrasi Akun</p>
                    <p class="mt-3 text-lg font-bold text-slate-900">Koneksi ke Akuntansi</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Akun aset, akumulasi depresiasi, dan beban depresiasi untuk posting jurnal otomatis.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status Halaman</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Siap Diisi Form Operasional</h3>
                </div>
                <div class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                    Struktur menu aktif
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Kode Aset</p>
                    <p class="mt-2 text-sm text-slate-500">Format kode unik per kategori aset.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Perolehan</p>
                    <p class="mt-2 text-sm text-slate-500">Tanggal beli, vendor, dan nilai awal aset.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Depresiasi</p>
                    <p class="mt-2 text-sm text-slate-500">Umur manfaat, residu, dan metode garis lurus.</p>
                </div>
                <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-5 py-5">
                    <p class="text-sm font-semibold text-slate-900">Status</p>
                    <p class="mt-2 text-sm text-slate-500">Aktif, dijual, rusak, atau dihentikan.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
