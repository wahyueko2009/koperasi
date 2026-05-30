@extends('layouts.app')

@section('content')
    @php
        $activeSection = collect($sections)->firstWhere('route', $currentRouteName);
        $otherSections = collect($sections)->reject(fn ($section) => $section['route'] === $currentRouteName)->values();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-sky-900 to-cyan-700 p-6 text-white shadow-xl shadow-slate-900/10">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-100">Buku Besar / GL</p>
                <h2 class="mt-3 text-3xl font-bold">{{ $activeSection['label'] }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-cyan-50/90">{{ $pageDescription }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm">
                        <i class="{{ $activeSection['icon'] }}"></i>
                        {{ $activeSection['summary'] }}
                    </span>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status Modul</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">Kerangka GL Sudah Siap</p>
                <p class="mt-3 text-sm leading-6 text-slate-500">Struktur penuh modul `Buku Besar / GL` sudah dipasang agar kita bisa lanjut isi master, jurnal, posting, buku besar, hingga laporan keuangan tanpa bongkar menu lagi.</p>
                <div class="mt-6 rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Arah pembangunan</p>
                    <p class="mt-2 text-sm text-slate-500">Mulai dari `Daftar Akun / COA`, lanjut ke `Kelompok Akun`, `Periode Akuntansi`, dan `Jurnal Umum`, lalu masuk ke posting serta laporan.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar Menu</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Submenu Buku Besar / GL</h3>
                </div>
                <div class="rounded-full bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">
                    13 submenu aktif
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sections as $section)
                    <a
                        href="{{ route($section['route']) }}"
                        class="rounded-[1.5rem] border px-5 py-5 transition {{ $section['route'] === $currentRouteName ? 'border-sky-300 bg-sky-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $section['route'] === $currentRouteName ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                            <i class="{{ $section['icon'] }}"></i>
                        </div>
                        <p class="mt-4 text-lg font-semibold text-slate-900">{{ $section['label'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $section['summary'] }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            @foreach ($otherSections->take(3) as $section)
                <a href="{{ route($section['route']) }}" class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Lanjutkan ke</p>
                    <p class="mt-3 text-xl font-bold text-slate-900">{{ $section['label'] }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $section['summary'] }}</p>
                </a>
            @endforeach
        </section>
    </div>
@endsection
