@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('gl.trial-balance') }}" class="grid gap-4 md:grid-cols-[1fr_0.35fr]">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Per Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div class="flex items-end">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Tampilkan Neraca Saldo</button>
                </div>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Total Debit</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900">Rp {{ number_format((float) $totalDebit, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-[1.75rem] border border-rose-100 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Total Kredit</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">Rp {{ number_format((float) $totalCredit, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-rose-700/80">Selisih: Rp {{ number_format(abs((float) $totalDebit - (float) $totalCredit), 0, ',', '.') }}</p>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Debit</th>
                            <th class="px-4 py-3">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $row->code }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row->name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ ucfirst($row->account_type) }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $row->debit_balance, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-rose-700">Rp {{ number_format((float) $row->credit_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada data neraca saldo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
