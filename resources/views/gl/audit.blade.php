@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('gl.audit') }}" class="grid gap-4 md:grid-cols-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        <option value="">Semua status</option>
                        @foreach (['draft' => 'Draft', 'posted' => 'Diposting'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Source Module</label>
                    <input name="source_module" value="{{ $filters['source_module'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="manual / piutang-usaha">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                </div>
                <div class="md:col-span-5">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Filter Audit Jurnal</button>
                </div>
            </form>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <th class="px-4 py-3">Referensi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Tag</th>
                            <th class="px-4 py-3">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($journals as $journal)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $journal->reference_number ?: 'Manual' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $journal->memo ?: 'Tanpa memo.' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->entry_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->source_module }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->debitAccount?->name }} -> {{ $journal->creditAccount?->name }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $journal->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $journal->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ strtoupper($journal->status) }}</span></td>
                                <td class="px-4 py-3">
                                    @if ($journal->is_legacy ?? false)
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">LEGACY</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">CURRENT</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <p>Dibuat: {{ $journal->creator?->name ?? 'Sistem' }}</p>
                                    <p>Diposting: {{ $journal->poster?->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $journal->posted_at?->format('d M Y H:i') ?: 'Belum diposting' }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada data audit jurnal.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
