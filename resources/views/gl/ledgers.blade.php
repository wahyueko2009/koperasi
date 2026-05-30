@extends('layouts.app')

@php
    $selectedAccount = $accounts->firstWhere('id', (int) ($filters['account_id'] ?? 0));
    $periodLabel = ($filters['date_from'] ?? null) || ($filters['date_to'] ?? null)
        ? trim(($filters['date_from'] ? \Illuminate\Support\Carbon::parse($filters['date_from'])->format('d M Y') : 'Awal') . ' - ' . ($filters['date_to'] ? \Illuminate\Support\Carbon::parse($filters['date_to'])->format('d M Y') : 'Sekarang'))
        : 'Semua periode';
@endphp

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('gl.ledgers') }}" class="grid gap-4 xl:grid-cols-[1.2fr_1fr_1fr_0.9fr_0.9fr]">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Akun</label>
                    <select name="account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        <option value="">Semua akun</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) ($filters['account_id'] ?? '') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div class="flex items-end">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Filter Buku Besar</button>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('gl.ledgers') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-center font-semibold text-slate-700 transition hover:bg-slate-50">
                        Reset Filter
                    </a>
                </div>
            </form>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-4">
                <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jumlah Record</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $entries->count() }}</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Akun Dipilih</p>
                    <p class="mt-2 truncate text-base font-bold text-slate-900">{{ $selectedAccount?->code ? $selectedAccount->code . ' - ' . $selectedAccount->name : 'Semua akun' }}</p>
                </div>
                <div class="rounded-[1.5rem] bg-emerald-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Total Debit</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-900">Rp {{ number_format((float) $totalDebit, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-[1.5rem] bg-rose-50 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Total Kredit</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900">Rp {{ number_format((float) $totalCredit, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Periode data: <span class="font-semibold text-slate-900">{{ $periodLabel }}</span>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="whitespace-nowrap px-4 py-3">No</th>
                                <th class="whitespace-nowrap px-4 py-3">Tanggal</th>
                                <th class="whitespace-nowrap px-4 py-3">Kode</th>
                                <th class="whitespace-nowrap px-4 py-3">Nama Akun</th>
                                <th class="whitespace-nowrap px-4 py-3">Referensi</th>
                                <th class="whitespace-nowrap px-4 py-3">Memo</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right">Debit</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($entries as $index => $entry)
                                <tr class="text-sm transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $index + 1 }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $entry->entry_date?->format('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $entry->account?->code ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $entry->account?->name ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $entry->journalEntry?->reference_number ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $entry->memo ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700">
                                        <span class="inline-block min-w-[140px] whitespace-nowrap text-right">Rp {{ number_format((float) $entry->debit, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-rose-700">
                                        <span class="inline-block min-w-[140px] whitespace-nowrap text-right">Rp {{ number_format((float) $entry->credit, 0, ',', '.') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada data buku besar untuk filter yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
