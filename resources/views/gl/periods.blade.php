@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('gl.periods.store') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Kode Periode</label>
                        <input name="code" value="{{ old('code', $periodCode) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="202605">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Nama Periode</label>
                        <input name="name" value="{{ old('name', now()->translatedFormat('F Y')) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Mei 2026">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="{{ old('start_date', now()->startOfMonth()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date', now()->endOfMonth()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            @foreach (['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'open') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan pembukaan periode, agenda closing, atau perhatian khusus.">{{ old('notes') }}</textarea>
                </div>

                <div class="grid gap-3 border-t border-slate-100 pt-2">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan Periode Akuntansi</button>
                    <button type="button" id="open-period-list" class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500">Lihat Data</button>
                </div>
            </form>
        </section>
    </div>

    <div id="period-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-period-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Master</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Periode Akuntansi</h3>
                    </div>
                    <button type="button" id="close-period-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">X</button>
                </div>
                <div class="max-h-[72vh] overflow-y-scroll overflow-x-hidden px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-4 py-3">Kode</th>
                                        <th class="px-4 py-3">Periode</th>
                                        <th class="px-4 py-3">Rentang</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Closed At</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($periods as $period)
                                        <tr class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $period->code }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $period->name }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $period->start_date?->format('d M Y') }} - {{ $period->end_date?->format('d M Y') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $period->status === 'closed' ? 'bg-slate-100 text-slate-700' : ($period->status === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">{{ strtoupper($period->status) }}</span></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $period->closed_at?->format('d M Y H:i') ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada periode akuntansi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('period-list-modal');
            const openButton = document.getElementById('open-period-list');
            const closeButton = document.getElementById('close-period-list');
            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };
            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-period-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
