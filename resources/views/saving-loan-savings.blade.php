@extends('layouts.app')

@php
    $title = 'Simpanan - Simpan Pinjam';
    $sectionLabel = 'Operasional Koperasi';
    $pageTitle = 'Simpanan';
    $pageDescription = 'Area simpanan untuk melihat setoran terakhir dan aktivitas simpanan anggota.';
@endphp

@section('content')
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Area Simpanan</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-900">Setoran Terbaru</h2>
            </div>
            <button type="button" data-open-transaction-modal="saving" class="inline-flex items-center gap-3 rounded-2xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                <i class="fas fa-plus"></i>
                Setoran Baru
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        <th class="px-6 py-4">Simpanan</th>
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentSavings as $saving)
                        @php
                            $detail = [
                                'type' => $saving->savingType?->name ?? 'Simpanan',
                                'member' => $saving->member?->name ?? '-',
                                'date' => optional($saving->deposit_date ?? $saving->created_at)?->format('d M Y H:i') ?? '-',
                                'amount' => (float) $saving->amount,
                                'status' => $saving->is_posted ? 'Diposting' : 'Draft',
                                'method' => ucfirst(str_replace('_', ' ', (string) $saving->payment_method)),
                                'notes' => $saving->notes ?? '-',
                            ];
                        @endphp
                        <tr class="cursor-pointer transition hover:bg-slate-50" data-saving-detail='@json($detail)'>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $saving->savingType?->name ?? 'Simpanan' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $saving->member?->name ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ optional($saving->deposit_date ?? $saving->created_at)?->format('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $saving->is_posted ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $saving->is_posted ? 'Diposting' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900">Rp {{ number_format((float) $saving->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">Belum ada setoran simpanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @include('partials.saving-loan-transaction-modals')

    <div id="saving-detail-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-950/55 px-4 py-8">
        <div class="w-full max-w-2xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detail Simpanan</p>
                    <h2 id="saving-detail-type" class="text-2xl font-bold text-slate-900">Simpanan</h2>
                </div>
                <button type="button" data-close-saving-detail class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-300 text-slate-500 hover:bg-slate-50">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="grid gap-4 px-6 py-6 sm:grid-cols-2">
                <div class="rounded-[1.5rem] bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Anggota</p><p id="saving-detail-member" class="mt-2 text-lg font-semibold text-slate-900">-</p></div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tanggal</p><p id="saving-detail-date" class="mt-2 text-lg font-semibold text-slate-900">-</p></div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Jumlah</p><p id="saving-detail-amount" class="mt-2 text-lg font-semibold text-slate-900">-</p></div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status</p><p id="saving-detail-status" class="mt-2 text-lg font-semibold text-slate-900">-</p></div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Metode</p><p id="saving-detail-method" class="mt-2 text-lg font-semibold text-slate-900">-</p></div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4 sm:col-span-2"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Catatan</p><p id="saving-detail-notes" class="mt-2 text-base text-slate-700">-</p></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('partials.saving-loan-transaction-scripts')
    <script>
        (() => {
            const modal = document.getElementById('saving-detail-modal');
            const rows = document.querySelectorAll('[data-saving-detail]');
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            rows.forEach((row) => {
                row.addEventListener('click', () => {
                    const detail = JSON.parse(row.dataset.savingDetail);
                    document.getElementById('saving-detail-type').textContent = detail.type || '-';
                    document.getElementById('saving-detail-member').textContent = detail.member || '-';
                    document.getElementById('saving-detail-date').textContent = detail.date || '-';
                    document.getElementById('saving-detail-amount').textContent = `Rp ${new Intl.NumberFormat('id-ID').format(detail.amount || 0)}`;
                    document.getElementById('saving-detail-status').textContent = detail.status || '-';
                    document.getElementById('saving-detail-method').textContent = detail.method || '-';
                    document.getElementById('saving-detail-notes').textContent = detail.notes || '-';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                });
            });

            document.querySelectorAll('[data-close-saving-detail]').forEach((button) => button.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => event.target === modal && closeModal());
            document.addEventListener('keydown', (event) => event.key === 'Escape' && !modal.classList.contains('hidden') && closeModal());
        })();
    </script>
@endpush
