@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Akun Aktif</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">{{ $activeAccountCount }}</p>
                <p class="mt-2 text-sm text-slate-500">Dari total {{ $accountCount }} akun / COA.</p>
            </div>
            <div class="rounded-[1.75rem] border border-amber-100 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Jurnal Draft</p>
                <p class="mt-3 text-3xl font-bold text-amber-800">{{ $draftJournalCount }}</p>
                <p class="mt-2 text-sm text-amber-700/80">Menunggu review atau posting ke buku besar.</p>
            </div>
            <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Jurnal Diposting</p>
                <p class="mt-3 text-3xl font-bold text-emerald-800">{{ $postedJournalCount }}</p>
                <p class="mt-2 text-sm text-emerald-700/80">Sudah masuk ke buku besar.</p>
            </div>
            <div class="rounded-[1.75rem] border border-rose-100 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Selisih Trial Balance</p>
                <p class="mt-3 text-3xl font-bold text-rose-800">Rp {{ number_format((float) $trialBalanceGap, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-rose-700/80">Target ideal: Rp 0.</p>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Utilitas Data</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Sinkronisasi Legacy</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Gunakan utilitas ini untuk mengikat jurnal lama ke periode aktif, menonaktifkan akun duplikat yang tidak terpakai, dan merapikan mapping lama yang sudah diganti alur baru.</p>
                </div>
                <div class="rounded-[1.5rem] bg-amber-50 px-5 py-4 text-sm text-amber-800">
                    <p class="font-semibold">Jurnal legacy terdeteksi: {{ $legacyJournalCount }}</p>
                    <p class="mt-1 text-amber-700/80">Aman dijalankan ulang jika masih ada data lama yang belum sinkron.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('gl.maintenance.sync-legacy') }}" class="mt-5">
                @csrf
                <button class="rounded-2xl bg-slate-900 px-5 py-3 font-semibold text-white hover:bg-slate-800">Sinkronisasi Data Legacy</button>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Periode Aktif</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ $openPeriod?->name ?: 'Belum ada periode open' }}</h3>
                    </div>
                    <span class="rounded-full px-4 py-2 text-sm font-semibold {{ $openPeriod ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                        {{ $openPeriod?->status ? ucfirst($openPeriod->status) : 'Kosong' }}
                    </span>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Mulai</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $openPeriod?->start_date?->format('d M Y') ?: '-' }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Selesai</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">{{ $openPeriod?->end_date?->format('d M Y') ?: '-' }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Mapping Aktif</p>
                        <p class="mt-2 text-lg font-bold text-cyan-800">{{ $mappingCount }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-violet-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">Pusat Biaya</p>
                        <p class="mt-2 text-lg font-bold text-violet-800">{{ $costCenterCount }}</p>
                    </div>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-500">{{ $openPeriod?->notes ?: 'Belum ada catatan periode. Setelah COA, jurnal, dan posting stabil, kita bisa review alur closing dengan lebih detail.' }}</p>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Saldo Ringkas</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Aset</p>
                        <p class="mt-2 text-xl font-bold text-cyan-900">Rp {{ number_format((float) $assetBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-amber-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Kewajiban</p>
                        <p class="mt-2 text-xl font-bold text-amber-900">Rp {{ number_format((float) $liabilityBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Pendapatan</p>
                        <p class="mt-2 text-xl font-bold text-emerald-900">Rp {{ number_format((float) $incomeBalance, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-[1.25rem] bg-rose-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Beban</p>
                        <p class="mt-2 text-xl font-bold text-rose-900">Rp {{ number_format((float) $expenseBalance, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Jurnal Terbaru</p>
                <div class="mt-5 space-y-3">
                    @forelse ($recentJournals as $journal)
                        <div class="rounded-[1.25rem] border border-slate-200 px-4 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $journal->reference_number ?: 'Jurnal Manual' }}</p>
                                    @if ($journal->is_legacy ?? false)
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">LEGACY</span>
                                    @endif
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $journal->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ strtoupper($journal->status) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">{{ $journal->debitAccount?->code }} {{ $journal->debitAccount?->name }} -> {{ $journal->creditAccount?->code }} {{ $journal->creditAccount?->name }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $journal->amount, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="rounded-[1.25rem] bg-slate-50 px-4 py-6 text-sm text-slate-500">Belum ada jurnal yang tersimpan.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Buku Besar Terbaru</p>
                <div class="mt-5 space-y-3">
                    @forelse ($recentLedgers as $entry)
                        <div class="rounded-[1.25rem] border border-slate-200 px-4 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ $entry->account?->code }} - {{ $entry->account?->name }}</p>
                                <p class="text-sm text-slate-500">{{ $entry->entry_date?->format('d M Y') }}</p>
                            </div>
                            <div class="mt-2 flex gap-4 text-sm">
                                <span class="font-semibold text-emerald-700">Db Rp {{ number_format((float) $entry->debit, 0, ',', '.') }}</span>
                                <span class="font-semibold text-rose-700">Cr Rp {{ number_format((float) $entry->credit, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $entry->memo ?: 'Tanpa memo.' }}</p>
                        </div>
                    @empty
                        <p class="rounded-[1.25rem] bg-slate-50 px-4 py-6 text-sm text-slate-500">Belum ada data buku besar.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
