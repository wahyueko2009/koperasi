@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('gl.closing.process') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-[1fr_0.8fr]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Periode yang Ditutup</label>
                        <select name="period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Pilih periode</option>
                            @foreach ($openPeriods as $period)
                                <option value="{{ $period->id }}">{{ $period->code }} - {{ $period->name }} ({{ strtoupper($period->status) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Closing</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Periode siap ditutup setelah jurnal penyesuaian selesai."></textarea>
                    </div>
                </div>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Tutup Periode</button>
            </form>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Periode Terbuka / Draft</p>
                <div class="mt-5 space-y-3">
                    @forelse ($openPeriods as $period)
                        <div class="rounded-[1.25rem] border border-slate-200 px-4 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ $period->code }} - {{ $period->name }}</p>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $period->status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ strtoupper($period->status) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">{{ $period->start_date?->format('d M Y') }} - {{ $period->end_date?->format('d M Y') }}</p>
                        </div>
                    @empty
                        <p class="rounded-[1.25rem] bg-slate-50 px-4 py-6 text-sm text-slate-500">Tidak ada periode yang masih terbuka.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Riwayat Closed</p>
                <div class="mt-5 space-y-3">
                    @forelse ($closedPeriods as $period)
                        <div class="rounded-[1.25rem] border border-slate-200 px-4 py-4">
                            <p class="font-semibold text-slate-900">{{ $period->code }} - {{ $period->name }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $period->closed_at?->format('d M Y H:i') ?: '-' }}</p>
                            <p class="mt-1 text-sm text-slate-500">Ditutup oleh: {{ $period->closer?->name ?? 'Sistem' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $period->notes ?: 'Tanpa catatan.' }}</p>
                        </div>
                    @empty
                        <p class="rounded-[1.25rem] bg-slate-50 px-4 py-6 text-sm text-slate-500">Belum ada periode yang ditutup.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection
