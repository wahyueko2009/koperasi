@extends('layouts.app')

@php
    $title = $title ?? 'Dashboard - Koperasi Digital Mandiri';
    $sectionLabel = $sectionLabel ?? 'Ringkasan Aplikasi';
    $pageTitle = $pageTitle ?? 'Dashboard Utama Koperasi';
    $pageDescription = $pageDescription ?? 'Pantau seluruh modul koperasi dari satu halaman.';

    $accentClasses = [
        'sky' => ['soft' => 'bg-sky-100 text-sky-700', 'ring' => 'ring-sky-200', 'button' => 'text-sky-700 hover:bg-sky-50'],
        'emerald' => ['soft' => 'bg-emerald-100 text-emerald-700', 'ring' => 'ring-emerald-200', 'button' => 'text-emerald-700 hover:bg-emerald-50'],
        'amber' => ['soft' => 'bg-amber-100 text-amber-700', 'ring' => 'ring-amber-200', 'button' => 'text-amber-700 hover:bg-amber-50'],
        'rose' => ['soft' => 'bg-rose-100 text-rose-700', 'ring' => 'ring-rose-200', 'button' => 'text-rose-700 hover:bg-rose-50'],
        'indigo' => ['soft' => 'bg-indigo-100 text-indigo-700', 'ring' => 'ring-indigo-200', 'button' => 'text-indigo-700 hover:bg-indigo-50'],
        'teal' => ['soft' => 'bg-teal-100 text-teal-700', 'ring' => 'ring-teal-200', 'button' => 'text-teal-700 hover:bg-teal-50'],
        'slate' => ['soft' => 'bg-slate-100 text-slate-700', 'ring' => 'ring-slate-200', 'button' => 'text-slate-700 hover:bg-slate-50'],
    ];
    $chartDatasets = collect($activityChart['datasets'] ?? [])
        ->map(function ($dataset) {
            return [
                'label' => $dataset['label'],
                'data' => $dataset['data'],
                'borderColor' => $dataset['borderColor'],
                'backgroundColor' => $dataset['backgroundColor'],
                'fill' => true,
                'tension' => 0.35,
            ];
        })
        ->values()
        ->all();
@endphp

@section('content')
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 px-6 py-6 text-white shadow-sm sm:px-8">
            <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-300">{{ $sectionLabel }}</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ $pageTitle }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">{{ $pageDescription }}</p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm text-slate-100">
                            {{ number_format($masterStats['members']) }} anggota
                        </span>
                        <span class="rounded-full bg-white/10 px-4 py-2 text-sm text-slate-100">
                            {{ number_format($masterStats['active_officials']) }} pengurus aktif
                        </span>
                        @if (!is_null($masterStats['active_accounts']))
                            <span class="rounded-full bg-white/10 px-4 py-2 text-sm text-slate-100">
                                {{ number_format($masterStats['active_accounts']) }} akun COA aktif
                            </span>
                        @endif
                        @if (!is_null($masterStats['inventory_count']))
                            <span class="rounded-full bg-white/10 px-4 py-2 text-sm text-slate-100">
                                {{ number_format($masterStats['inventory_count']) }} inventory aktif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10 backdrop-blur">
                        <p class="text-sm text-slate-300">Anggota nonaktif</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($masterStats['inactive_members']) }}</p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10 backdrop-blur">
                        <p class="text-sm text-slate-300">Master pengurus</p>
                        <p class="mt-2 text-2xl font-bold">{{ number_format($masterStats['officials']) }}</p>
                    </div>
                    @if (!is_null($masterStats['company_count']))
                        <div class="rounded-3xl bg-white/10 p-5 ring-1 ring-white/10 backdrop-blur">
                            <p class="text-sm text-slate-300">Perusahaan aktif</p>
                            <p class="mt-2 text-2xl font-bold">{{ number_format($masterStats['company_count']) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($highlightCards as $card)
                @php($classes = $accentClasses[$card['accent']] ?? $accentClasses['slate'])
                <div class="rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $card['value'] }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">{{ $card['caption'] }}</p>
                        </div>
                        <div class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl {{ $classes['soft'] }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.45fr_0.95fr]">
            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Tren Aktivitas 6 Bulan</h2>
                        <p class="text-sm text-slate-500">Pergerakan utama antar modul yang paling sering dipantau manajemen.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Lintas Modul</span>
                </div>
                <div class="mt-6 h-80">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Perlu Perhatian</h2>
                        <p class="text-sm text-slate-500">Daftar hal yang paling cepat perlu ditindaklanjuti.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ number_format($attentionItems->count()) }} item</span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($attentionItems as $item)
                        @php($classes = $accentClasses[$item['tone']] ?? $accentClasses['slate'])
                        <a href="{{ route($item['route']) }}" class="flex items-start justify-between gap-4 rounded-3xl border border-slate-200 px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['label'] }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $item['detail'] }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $classes['soft'] }}">Tindak lanjut</span>
                        </a>
                    @empty
                        <div class="rounded-3xl bg-slate-50 px-4 py-6 text-sm leading-6 text-slate-500">
                            Belum ada item yang mendesak. Dashboard ini sudah relatif bersih untuk saat ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Ringkasan Per Modul</h2>
                <p class="text-sm text-slate-500">Setiap blok di bawah ini merangkum isi menu kerja beserta shortcut pentingnya.</p>
            </div>

            <div class="grid gap-5 2xl:grid-cols-2">
                @foreach ($modulePanels as $panel)
                    @php($classes = $accentClasses[$panel['accent']] ?? $accentClasses['slate'])
                    <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $classes['soft'] }}">
                                        <i class="{{ $panel['icon'] }}"></i>
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900">{{ $panel['title'] }}</h3>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $panel['description'] }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($panel['stats'] as $stat)
                                <div class="rounded-3xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                                    <p class="mt-2 text-lg font-bold text-slate-900">{{ $stat['value'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($panel['shortcuts'] as $shortcut)
                                <a href="{{ route($shortcut['route']) }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium transition {{ $classes['button'] }}">
                                    {{ $shortcut['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Aktivitas Terbaru</h2>
                        <p class="text-sm text-slate-500">Jejak transaksi penting dari modul-modul yang sudah terintegrasi.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ number_format($recentActivities->count()) }} aktivitas</span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Aktivitas</th>
                                <th class="px-4 py-3">Modul</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($recentActivities as $activity)
                                @php($classes = $accentClasses[$activity['accent']] ?? $accentClasses['slate'])
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-2xl {{ $classes['soft'] }}">
                                                <i class="{{ $activity['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $activity['label'] }}</p>
                                                <p class="mt-1 text-slate-500">{{ $activity['detail'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $classes['soft'] }}">{{ $activity['badge'] }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ optional($activity['date'])->format('d M Y') }}</td>
                                    <td class="px-4 py-4 text-right font-semibold text-slate-900">Rp {{ number_format((float) $activity['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Master Data Singkat</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan elemen master yang paling sering dipakai lintas modul.</p>

                <div class="mt-5 grid gap-3">
                    <div class="rounded-3xl bg-slate-50 px-4 py-4">
                        <p class="text-sm text-slate-500">Total anggota</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['members']) }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 px-4 py-4">
                        <p class="text-sm text-slate-500">Pengurus aktif</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['active_officials']) }}</p>
                    </div>
                    @if (!is_null($masterStats['users']))
                        <div class="rounded-3xl bg-slate-50 px-4 py-4">
                            <p class="text-sm text-slate-500">User sistem</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['users']) }}</p>
                        </div>
                    @endif
                    @if (!is_null($masterStats['positions']))
                        <div class="rounded-3xl bg-slate-50 px-4 py-4">
                            <p class="text-sm text-slate-500">Posisi / jabatan</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['positions']) }}</p>
                        </div>
                    @endif
                    @if (!is_null($masterStats['service_count']))
                        <div class="rounded-3xl bg-slate-50 px-4 py-4">
                            <p class="text-sm text-slate-500">Jasa aktif unit usaha</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['service_count']) }}</p>
                        </div>
                    @endif
                    @if (!is_null($masterStats['contract_count']))
                        <div class="rounded-3xl bg-slate-50 px-4 py-4">
                            <p class="text-sm text-slate-500">Kontrak usaha aktif</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($masterStats['contract_count']) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        const activityChart = document.getElementById('activityChart');
        if (activityChart) {
            new Chart(activityChart, {
                type: 'line',
                data: {
                    labels: @json($activityChart['months']),
                    datasets: @json($chartDatasets),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            labels: {
                                usePointStyle: true,
                            },
                        },
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback(value) {
                                    return 'Rp ' + Number(value).toLocaleString('id-ID');
                                },
                            },
                        },
                    },
                },
            });
        }
    </script>
@endpush
