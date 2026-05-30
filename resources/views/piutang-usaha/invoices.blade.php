@extends('layouts.app')

@php
    $issuedInvoices = $invoices->whereIn('status', ['issued', 'partial', 'paid'])->count();
    $overdueInvoices = $invoices->filter(fn ($invoice) => $invoice->isOverdue())->count();
    $todayBilling = $invoices->where('invoice_date', now()->toDateString())->sum('total_amount');
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-6xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <form method="POST" action="{{ route('piutang-usaha.invoices.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Invoice</label>
                            <input name="invoice_number" value="{{ old('invoice_number', $invoiceNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="INV-20260528-001">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Kontrak Jasa</label>
                            <select name="contract_id" id="contract-select" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih kontrak aktif</option>
                                @foreach ($contracts as $contract)
                                    <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>
                                        {{ $contract->contract_number }} - {{ $contract->company?->name }} - {{ $contract->service_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Invoice</label>
                            <input name="invoice_date" type="date" value="{{ old('invoice_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Periode Awal</label>
                            <input name="period_start" type="date" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Periode Akhir</label>
                            <input name="period_end" type="date" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jatuh Tempo</label>
                            <input name="due_date" type="date" value="{{ old('due_date', now()->endOfMonth()->addDays(30)->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-[1fr_0.8fr]">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['draft' => 'Draft', 'issued' => 'Terbit'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'issued') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Termin Pembayaran (Hari)</label>
                            <input name="payment_term_days" id="payment-term-days" type="number" min="0" value="{{ old('payment_term_days', 30) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Perusahaan</p>
                            <p id="contract-company" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kategori</p>
                            <p id="contract-category" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Siklus</p>
                            <p id="contract-cycle" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Periode Kontrak</p>
                            <p id="contract-period" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Preview Item</p>
                                <p class="mt-1 text-sm text-slate-500">Item invoice otomatis mengikuti komponen kontrak yang dipilih.</p>
                            </div>
                            <div class="rounded-full bg-cyan-50 px-3 py-1.5 text-sm font-semibold text-cyan-700">
                                <span id="contract-item-count">0</span> item
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-4 py-3">Item</th>
                                        <th class="px-4 py-3">Qty</th>
                                        <th class="px-4 py-3">Harga</th>
                                        <th class="px-4 py-3">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody id="contract-item-preview" class="divide-y divide-slate-100 bg-white">
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Pilih kontrak untuk melihat item invoice.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-[1.25rem] border border-cyan-100 bg-cyan-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Total Tagihan</p>
                        <p id="invoice-grand-total" class="mt-2 text-xl font-bold text-cyan-900">Rp 0</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Invoice</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan periode kerja, dokumen pendukung, atau arahan penagihan.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="grid gap-3 border-t border-slate-100 pt-2">
                        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                            Simpan Tagihan / Invoice
                        </button>
                        <button
                            type="button"
                            id="open-invoice-list"
                            class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500"
                        >
                            View Data
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="invoice-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-invoice-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Transaksi</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Tagihan / Invoice</h3>
                    </div>
                    <button type="button" id="close-invoice-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup popup data invoice">X</button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $invoices->count() }}</p></div>
                        <div class="rounded-[1.25rem] bg-emerald-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Terbit</p><p class="mt-2 text-xl font-bold text-emerald-800">{{ $issuedInvoices }}</p></div>
                        <div class="rounded-[1.25rem] bg-rose-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Jatuh Tempo</p><p class="mt-2 text-xl font-bold text-rose-800">{{ $overdueInvoices }}</p></div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Hari Ini</p><p class="mt-2 text-lg font-bold text-cyan-800">Rp {{ number_format((float) $todayBilling, 0, ',', '.') }}</p></div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Invoice</th>
                                        <th class="px-5 py-3">Perusahaan</th>
                                        <th class="px-5 py-3">Periode</th>
                                        <th class="px-5 py-3">Tagihan</th>
                                        <th class="px-5 py-3">Sisa</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($invoices as $invoice)
                                        <tr>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ optional($invoice->invoice_date)->format('d M Y') }} · JT {{ optional($invoice->due_date)->format('d M Y') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $invoice->company?->name ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $invoice->contract?->contract_number ?: '-' }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ optional($invoice->period_start)->format('d M Y') }} - {{ optional($invoice->period_end)->format('d M Y') }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4">
                                                <p class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</p>
                                                <p class="mt-1 text-sm text-slate-500">Bayar Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{
                                                    $invoice->status === 'paid' ? 'bg-emerald-100 text-emerald-700'
                                                    : ($invoice->isOverdue() ? 'bg-rose-100 text-rose-700'
                                                    : ($invoice->status === 'partial' ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-slate-100 text-slate-600'))
                                                }}">{{ $invoice->statusLabel() }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada invoice yang tersimpan.</td></tr>
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
            const contractSelect = document.getElementById('contract-select');
            const paymentTermInput = document.getElementById('payment-term-days');
            const companyElement = document.getElementById('contract-company');
            const categoryElement = document.getElementById('contract-category');
            const cycleElement = document.getElementById('contract-cycle');
            const periodElement = document.getElementById('contract-period');
            const itemCountElement = document.getElementById('contract-item-count');
            const previewBody = document.getElementById('contract-item-preview');
            const totalElement = document.getElementById('invoice-grand-total');
            const modal = document.getElementById('invoice-list-modal');
            const openButton = document.getElementById('open-invoice-list');
            const closeButton = document.getElementById('close-invoice-list');
            const contracts = {!! $contractsJson !!};

            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

            const renderContractPreview = () => {
                const contractId = Number(contractSelect?.value || 0);
                const contract = contracts.find((item) => item.id === contractId);

                if (!contract) {
                    companyElement.textContent = '-';
                    categoryElement.textContent = '-';
                    cycleElement.textContent = '-';
                    periodElement.textContent = '-';
                    itemCountElement.textContent = '0';
                    totalElement.textContent = 'Rp 0';
                    previewBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Pilih kontrak untuk melihat item invoice.</td></tr>';
                    return;
                }

                companyElement.textContent = contract.company_name || '-';
                categoryElement.textContent = contract.service_category || '-';
                cycleElement.textContent = contract.billing_cycle_label || '-';
                periodElement.textContent = contract.period_label || '-';
                itemCountElement.textContent = String(contract.items.length);
                totalElement.textContent = formatCurrency(contract.total_amount);

                if (!paymentTermInput.dataset.touched) {
                    paymentTermInput.value = contract.payment_term_days;
                }

                previewBody.innerHTML = contract.items.map((item) => `
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-900">${item.item_name}</p>
                            <p class="mt-1 text-sm text-slate-500">${item.description || '-'}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700">${item.quantity} ${item.unit}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">${formatCurrency(item.unit_price)}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">${formatCurrency(item.subtotal)}</td>
                    </tr>
                `).join('');
            };

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            paymentTermInput?.addEventListener('input', () => {
                paymentTermInput.dataset.touched = '1';
            });

            contractSelect?.addEventListener('change', () => {
                delete paymentTermInput.dataset.touched;
                renderContractPreview();
            });

            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-invoice-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });

            renderContractPreview();
        })();
    </script>
@endpush
