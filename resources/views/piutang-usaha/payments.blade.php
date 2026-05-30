@extends('layouts.app')

@php
    $todayPayments = $payments->where('payment_date', now()->toDateString())->sum('amount');
    $partialInvoices = $invoices->filter(fn ($invoice) => $invoice->status === 'partial')->count();
    $openInvoices = $invoices->filter(fn ($invoice) => $invoice->outstanding_amount > 0)->count();
@endphp

@section('content')
    <div class="space-y-6">
        <div class="mx-auto max-w-6xl">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <form method="POST" action="{{ route('piutang-usaha.payments.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Nomor Pembayaran</label>
                            <input name="payment_number" value="{{ old('payment_number', $paymentNumber) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="PAY-20260528-001">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Invoice</label>
                            <select name="invoice_id" id="invoice-select" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="">Pilih invoice</option>
                                @foreach ($invoices as $invoice)
                                    <option value="{{ $invoice->id }}" @selected((string) old('invoice_id') === (string) $invoice->id)>
                                        {{ $invoice->invoice_number }} - {{ $invoice->company?->name }} - Sisa Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tanggal Bayar</label>
                            <input name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Jumlah Bayar</label>
                            <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="5000000">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Metode Bayar</label>
                            <select name="payment_method" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                @foreach (['transfer' => 'Transfer', 'giro' => 'Giro', 'cash' => 'Tunai', 'lainnya' => 'Lainnya'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', 'transfer') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Referensi</label>
                            <input name="reference_number" value="{{ old('reference_number') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nomor transfer / giro">
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Perusahaan</p>
                            <p id="invoice-company" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Jatuh Tempo</p>
                            <p id="invoice-due-date" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</p>
                            <p id="invoice-status" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kondisi</p>
                            <p id="invoice-overdue" class="mt-2 text-sm font-semibold text-slate-900">-</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-white px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Tagihan</p>
                            <p id="invoice-total-amount" class="mt-2 text-lg font-bold text-slate-900">Rp 0</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-amber-100 bg-amber-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Sudah Dibayar</p>
                            <p id="invoice-paid-amount" class="mt-2 text-lg font-bold text-amber-800">Rp 0</p>
                        </div>
                        <div class="rounded-[1.25rem] border border-cyan-100 bg-cyan-50 px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Sisa Piutang</p>
                            <p id="invoice-outstanding-amount" class="mt-2 text-lg font-bold text-cyan-900">Rp 0</p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Catatan Pembayaran</label>
                        <textarea name="notes" rows="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Catatan pelunasan, transfer, atau informasi pembayaran lain.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="grid gap-3 border-t border-slate-100 pt-2">
                        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white hover:bg-slate-800">
                            Simpan Pembayaran Piutang
                        </button>
                        <button
                            type="button"
                            id="open-payment-list"
                            class="w-full rounded-2xl bg-sky-600 px-4 py-3 font-semibold text-white hover:bg-sky-500"
                        >
                            View Data
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div id="payment-list-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/45" data-close-payment-list></div>
        <div class="relative flex min-h-full items-center justify-center p-4 md:p-6">
            <section class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tabel Transaksi</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Daftar Pembayaran Piutang</h3>
                    </div>
                    <button type="button" id="close-payment-list" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-lg font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup popup data pembayaran">X</button>
                </div>

                <div class="space-y-6 overflow-y-auto px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Pembayaran</p><p class="mt-2 text-xl font-bold text-slate-900">{{ $payments->count() }}</p></div>
                        <div class="rounded-[1.25rem] bg-cyan-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Hari Ini</p><p class="mt-2 text-lg font-bold text-cyan-800">Rp {{ number_format((float) $todayPayments, 0, ',', '.') }}</p></div>
                        <div class="rounded-[1.25rem] bg-amber-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Invoice Partial</p><p class="mt-2 text-xl font-bold text-amber-800">{{ $partialInvoices }}</p></div>
                        <div class="rounded-[1.25rem] bg-rose-50 px-4 py-3"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Invoice Belum Lunas</p><p class="mt-2 text-xl font-bold text-rose-800">{{ $openInvoices }}</p></div>
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th class="px-5 py-3">Pembayaran</th>
                                        <th class="px-5 py-3">Invoice</th>
                                        <th class="px-5 py-3">Perusahaan</th>
                                        <th class="px-5 py-3">Metode</th>
                                        <th class="px-5 py-3">Jumlah</th>
                                        <th class="px-5 py-3">Sisa Invoice</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($payments as $payment)
                                        <tr>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $payment->payment_number }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ optional($payment->payment_date)->format('d M Y') }}</p>
                                            </td>
                                            <td class="px-5 py-4">
                                                <p class="font-semibold text-slate-900">{{ $payment->invoice?->invoice_number ?: '-' }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ $payment->reference_number ?: 'Tanpa referensi' }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ $payment->invoice?->company?->name ?: '-' }}</td>
                                            <td class="px-5 py-4 text-sm text-slate-700">{{ ucfirst($payment->payment_method) }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) ($payment->invoice?->outstanding_amount ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pembayaran piutang yang tersimpan.</td></tr>
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
            const invoiceSelect = document.getElementById('invoice-select');
            const companyElement = document.getElementById('invoice-company');
            const dueDateElement = document.getElementById('invoice-due-date');
            const statusElement = document.getElementById('invoice-status');
            const overdueElement = document.getElementById('invoice-overdue');
            const totalAmountElement = document.getElementById('invoice-total-amount');
            const paidAmountElement = document.getElementById('invoice-paid-amount');
            const outstandingAmountElement = document.getElementById('invoice-outstanding-amount');
            const modal = document.getElementById('payment-list-modal');
            const openButton = document.getElementById('open-payment-list');
            const closeButton = document.getElementById('close-payment-list');
            const invoices = {!! $invoicesJson !!};

            const formatCurrency = (value) => `Rp ${new Intl.NumberFormat('id-ID').format(value)}`;

            const renderInvoiceInfo = () => {
                const invoiceId = Number(invoiceSelect?.value || 0);
                const invoice = invoices.find((item) => item.id === invoiceId);

                if (!invoice) {
                    companyElement.textContent = '-';
                    dueDateElement.textContent = '-';
                    statusElement.textContent = '-';
                    overdueElement.textContent = '-';
                    totalAmountElement.textContent = 'Rp 0';
                    paidAmountElement.textContent = 'Rp 0';
                    outstandingAmountElement.textContent = 'Rp 0';
                    return;
                }

                companyElement.textContent = invoice.company_name || '-';
                dueDateElement.textContent = invoice.due_date_label || '-';
                statusElement.textContent = invoice.status_label || '-';
                overdueElement.textContent = invoice.is_overdue ? 'Overdue / Lewat jatuh tempo' : 'Masih dalam termin';
                totalAmountElement.textContent = formatCurrency(invoice.total_amount);
                paidAmountElement.textContent = formatCurrency(invoice.paid_amount);
                outstandingAmountElement.textContent = formatCurrency(invoice.outstanding_amount);
            };

            const toggleModal = (show) => {
                if (!modal) return;
                modal.classList.toggle('hidden', !show);
                document.body.classList.toggle('overflow-hidden', show);
            };

            invoiceSelect?.addEventListener('change', renderInvoiceInfo);
            openButton?.addEventListener('click', () => toggleModal(true));
            closeButton?.addEventListener('click', () => toggleModal(false));
            modal?.querySelectorAll('[data-close-payment-list]').forEach((element) => {
                element.addEventListener('click', () => toggleModal(false));
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') toggleModal(false);
            });

            renderInvoiceInfo();
        })();
    </script>
@endpush
