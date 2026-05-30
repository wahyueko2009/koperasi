@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Saldo Piutang</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalOutstanding, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $openInvoices->count() }} invoice masih terbuka.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Tagihan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalBilled, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $invoices->count() }} invoice tercatat.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Total Pembayaran</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $totalPaid, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $payments->count() }} transaksi pembayaran tercatat.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Outstanding Overdue</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">Rp {{ number_format((float) $overdueTotal, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Butuh tindak lanjut penagihan segera.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Aging Piutang</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Umur Piutang</h3>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center justify-between rounded-[1.25rem] bg-slate-50 px-4 py-4">
                        <span class="text-sm font-semibold text-slate-700">Belum Jatuh Tempo</span>
                        <span class="text-sm font-bold text-slate-900">Rp {{ number_format((float) $aging['current'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-[1.25rem] bg-amber-50 px-4 py-4">
                        <span class="text-sm font-semibold text-amber-700">Overdue 1-30 Hari</span>
                        <span class="text-sm font-bold text-amber-800">Rp {{ number_format((float) $aging['overdue_1_30'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-[1.25rem] bg-orange-50 px-4 py-4">
                        <span class="text-sm font-semibold text-orange-700">Overdue 31-60 Hari</span>
                        <span class="text-sm font-bold text-orange-800">Rp {{ number_format((float) $aging['overdue_31_60'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-[1.25rem] bg-rose-50 px-4 py-4">
                        <span class="text-sm font-semibold text-rose-700">Overdue 61-90 Hari</span>
                        <span class="text-sm font-bold text-rose-800">Rp {{ number_format((float) $aging['overdue_61_90'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-[1.25rem] bg-red-50 px-4 py-4">
                        <span class="text-sm font-semibold text-red-700">Overdue > 90 Hari</span>
                        <span class="text-sm font-bold text-red-800">Rp {{ number_format((float) $aging['overdue_90_plus'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Debitur Terbesar</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Top Saldo Piutang</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($topDebtors as $debtor)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $debtor->company_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $debtor->invoice_count }} invoice terbuka</p>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $debtor->outstanding_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada saldo piutang yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Rekap Invoice</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Status Tagihan</h3>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-5 py-3">Perusahaan</th>
                                <th class="px-5 py-3">Periode</th>
                                <th class="px-5 py-3">Total</th>
                                <th class="px-5 py-3">Dibayar</th>
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
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $invoice->company?->name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ optional($invoice->period_start)->format('d M Y') }} - {{ optional($invoice->period_end)->format('d M Y') }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</td>
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
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada invoice yang bisa direkap.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Histori Pembayaran</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Penerimaan Piutang</h3>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-5 py-3">Pembayaran</th>
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-5 py-3">Perusahaan</th>
                                <th class="px-5 py-3">Metode</th>
                                <th class="px-5 py-3">Referensi</th>
                                <th class="px-5 py-3">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $payment->payment_number }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ optional($payment->payment_date)->format('d M Y') }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $payment->invoice?->invoice_number ?: '-' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $payment->invoice?->company?->name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ ucfirst($payment->payment_method) }}</td>
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ $payment->reference_number ?: '-' }}</td>
                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pembayaran yang bisa direkap.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
