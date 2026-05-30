@extends('layouts.app')

@php
    $readyCount = $previewRows->where('eligible', true)->count();
    $draftCount = $existingDepreciations->where('status', 'draft')->count();
    $postedCount = $existingDepreciations->where('status', 'posted')->count();
    $periodTotal = $existingDepreciations->sum(fn ($item) => (float) $item->amount);
@endphp

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Periode</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $periodEnd->translatedFormat('F Y') }}</p>
                <p class="mt-2 text-sm text-slate-500">Periode depresiasi yang sedang direview.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Siap Diproses</p>
                <p class="mt-3 text-3xl font-bold text-cyan-700">{{ number_format($readyCount) }}</p>
                <p class="mt-2 text-sm text-slate-500">Aset yang belum diproses untuk periode ini.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Draft</p>
                <p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($draftCount) }}</p>
                <p class="mt-2 text-sm text-slate-500">Bisa diposting ke jurnal satu per satu.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nilai Periode</p>
                <p class="mt-3 text-3xl font-bold text-emerald-700">Rp {{ number_format($periodTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $postedCount }} baris sudah diposting.</p>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Generate Depresiasi</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Jalankan Periode</h3>
                    <p class="mt-2 text-sm text-slate-500">Sistem memakai metode garis lurus dan mencegah double posting untuk aset pada bulan yang sama.</p>
                </div>

                <form method="GET" action="{{ route('fixed-assets.depreciation') }}" class="grid gap-3 md:grid-cols-3">
                    <select name="month" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                        @foreach (range(1, 12) as $month)
                            <option value="{{ $month }}" @selected($selectedMonth === $month)>{{ sprintf('%02d', $month) }}</option>
                        @endforeach
                    </select>
                    <input name="year" type="number" min="2020" max="2100" value="{{ $selectedYear }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                    <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
                </form>
            </div>

            <form method="POST" action="{{ route('fixed-assets.depreciation.run') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">

                <label class="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <input type="checkbox" name="post_to_journal" value="1" class="rounded border-emerald-300">
                    Langsung posting jurnal depresiasi ke GL saat proses dijalankan
                </label>

                <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Generate Depresiasi Periode {{ sprintf('%02d/%04d', $selectedMonth, $selectedYear) }}
                </button>
            </form>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="border-b border-slate-100 pb-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Preview</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-900">Aset yang Akan Diproses</h3>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Aset</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Beban</th>
                                <th class="px-5 py-3">Akumulasi</th>
                                <th class="px-5 py-3">Nilai Buku</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($previewRows as $row)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $row['asset_code'] }} - {{ $row['asset_name'] }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $row['category'] }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['eligible'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $row['accumulated_amount'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $row['book_value'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada aset yang dapat dipreview.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="border-b border-slate-100 pb-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Histori Periode</p>
                <h3 class="mt-2 text-2xl font-bold text-slate-900">Depresiasi {{ sprintf('%02d/%04d', $selectedMonth, $selectedYear) }}</h3>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Aset</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Jumlah</th>
                                <th class="px-5 py-3">Nilai Buku</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($existingDepreciations as $item)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $item->asset?->asset_code }} - {{ $item->asset?->asset_name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $item->asset?->category }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ optional($item->depreciation_date)->format('d M Y') }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $item->book_value, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $item->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ strtoupper($item->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($item->status !== 'posted')
                                            <form method="POST" action="{{ route('fixed-assets.depreciation.post', $item) }}">
                                                @csrf
                                                <button class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                    Posting Jurnal
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-500">{{ $item->journalEntry?->reference_number ?: 'Terkoneksi ke jurnal' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada histori depresiasi untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
