@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Antrian Posting</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Jurnal Draft</h3>
                </div>
                <span class="rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">{{ $draftJournals->count() }} jurnal draft</span>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($draftJournals as $journal)
                    <form method="POST" action="{{ route('gl.posting.process', $journal) }}" class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                        @csrf
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $journal->reference_number ?: 'Jurnal Draft' }}</p>
                                    @if ($journal->is_legacy ?? false)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">LEGACY</span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-500">{{ $journal->entry_date?->format('d M Y') }} · {{ $journal->memo ?: 'Tanpa memo' }}</p>
                                <p class="text-sm text-slate-700">{{ $journal->debitAccount?->code }} {{ $journal->debitAccount?->name }} -> {{ $journal->creditAccount?->code }} {{ $journal->creditAccount?->name }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $journal->amount, 0, ',', '.') }}</p>
                                <button class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Posting Sekarang</button>
                            </div>
                        </div>
                    </form>
                @empty
                    <div class="rounded-[1.5rem] bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Tidak ada jurnal draft yang menunggu posting.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jurnal Sudah Diposting</p>
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <th class="px-4 py-3">Referensi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Tag</th>
                            <th class="px-4 py-3">Waktu Posting</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($postedJournals as $journal)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $journal->reference_number ?: 'Manual' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->entry_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->debitAccount?->name }} -> {{ $journal->creditAccount?->name }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $journal->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @if ($journal->is_legacy ?? false)
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">LEGACY</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">CURRENT</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $journal->posted_at?->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada jurnal yang diposting.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
