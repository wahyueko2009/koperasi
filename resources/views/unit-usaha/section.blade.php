@extends('layouts.app')

@section('content')
    @php
        $activeSection = collect($sections)->firstWhere('route', $currentRouteName);
        $otherSections = collect($sections)->reject(fn ($section) => $section['route'] === $currentRouteName)->values();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-900 via-slate-800 to-teal-800 p-6 text-white shadow-xl shadow-slate-900/10">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-teal-100">Unit Usaha</p>
                <h2 class="mt-3 text-3xl font-bold">{{ $activeSection['label'] }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">{{ $pageDescription }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm">
                        <i class="{{ $activeSection['icon'] }}"></i>
                        {{ $activeSection['summary'] }}
                    </span>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status Halaman</p>
                <p class="mt-3 text-2xl font-bold text-slate-900">Struktur Menu Siap</p>
                <p class="mt-3 text-sm leading-6 text-slate-500">Menu dan route untuk modul ini sudah disiapkan. Tahap berikutnya kita bisa isi fitur operasional per halaman sesuai prioritas implementasi.</p>
                <div class="mt-6 rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Prioritas yang disarankan</p>
                    <p class="mt-2 text-sm text-slate-500">Mulai dari `Kasir / POS`, lalu `Stok & Inventory`, dan setelah itu `Master Produk/Jasa`.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Daftar Menu</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Submenu Unit Usaha</h3>
                </div>
                <div class="rounded-full bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700">
                    7 submenu aktif
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($sections as $section)
                    <a
                        href="{{ route($section['route']) }}"
                        class="rounded-[1.5rem] border px-5 py-5 transition {{ $section['route'] === $currentRouteName ? 'border-teal-300 bg-teal-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $section['route'] === $currentRouteName ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700' }}">
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
