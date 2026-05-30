@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('gl.journals.store') }}" class="space-y-5">
                @csrf
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Jurnal</label>
                        <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Periode</label>
                        <select name="period_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Pilih periode</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((string) old('period_id') === (string) $period->id)>{{ $period->code }} - {{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Referensi Jurnal</label>
                        <input name="reference_number" value="{{ old('reference_number', $referenceNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="JRN-20260528-001">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Debit</label>
                        <select name="debit_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Pilih akun debit</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('debit_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Akun Kredit</label>
                        <select name="credit_account_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Pilih akun kredit</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('credit_account_id') === (string) $account->id)>{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Jumlah</label>
                        <input type="number" min="0.01" step="0.01" name="amount" value="{{ old('amount') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="1000000">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Pusat Biaya</label>
                        <select name="cost_center_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Tanpa pusat biaya</option>
                            @foreach ($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}" @selected((string) old('cost_center_id') === (string) $costCenter->id)>{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            @foreach (['draft' => 'Draft', 'posted' => 'Diposting'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Memo</label>
                    <textarea name="memo" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Penyesuaian kas kecil bulan berjalan.">{{ old('memo') }}</textarea>
                </div>

                <div class="grid gap-3 border-t border-slate-100 pt-2">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">Simpan Jurnal Umum</button>
                    <button type="button" id="open-journal-list" class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500">Lihat Data</button>
                </div>
            </form>
        </section>
    </div>

    <div id="journal-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-journal-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Transaksi</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Jurnal Umum</h3>
                    </div>
                    <button type="button" id="close-journal-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">X</button>
                </div>
                <div class="max-h-[72vh] overflow-y-scroll overflow-x-hidden px-6 py-6">
                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-fixed divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-4 py-3">Ref</th>
                                        <th class="px-4 py-3">Tanggal</th>
                                        <th class="px-4 py-3">Periode</th>
                                        <th class="px-4 py-3">Debit</th>
                                        <th class="px-4 py-3">Kredit</th>
                                        <th class="px-4 py-3">Jumlah</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Tag</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($journals as $journal)
                                        <tr class="text-sm">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $journal->reference_number ?: 'Manual' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $journal->entry_date?->format('d M Y') }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $journal->period?->name ?: '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $journal->debitAccount?->code }} {{ $journal->debitAccount?->name }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ $journal->creditAccount?->code }} {{ $journal->creditAccount?->name }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">Rp {{ number_format((float) $journal->amount, 0, ',', '.') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $journal->status === 'posted' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ strtoupper($journal->status) }}</span></td>
                                            <td class="whitespace-nowrap px-4 py-3">
                                                @if ($journal->is_legacy ?? false)
                                                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">LEGACY</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">CURRENT</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada jurnal umum.</td></tr>
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
            const modal = document.getElementById('journal-list-modal');
            const openButton = document.getElementById('open-journal-list');
            const closeButton = document.getElementById('close-journal-list');
            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };
            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-journal-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });
        })();
    </script>
@endpush
