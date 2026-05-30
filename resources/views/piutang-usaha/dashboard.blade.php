@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Piutang Berjalan</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $openInvoices->sum('outstanding_amount'), 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $openInvoices->count() }} invoice belum lunas.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tagihan Bulan Ini</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $billedThisMonth, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi invoice periode bulan berjalan.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pembayaran Bulan Ini</p>
                <p class="mt-3 text-3xl font-bold text-slate-900">Rp {{ number_format((float) $receivedThisMonth, 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $recentPayments->count() }} pembayaran terakhir tampil di panel aktivitas.</p>
            </div>
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Overdue</p>
                <p class="mt-3 text-3xl font-bold text-rose-600">{{ $overdueInvoices->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Perusahaan aktif {{ $activeCompanyCount }} · kontrak aktif {{ $activeContractCount }}.</p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tindak Lanjut</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Invoice Jatuh Tempo</h3>
                    </div>
                    <span class="rounded-full bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700">
                        {{ $overdueInvoices->count() }} invoice
                    </span>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($overdueInvoices->take(6) as $invoice)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $invoice->company?->name ?: '-' }} · JT {{ optional($invoice->due_date)->format('d M Y') }}</p>
                                </div>
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                    {{ $invoice->statusLabel() }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-slate-500">Sisa piutang</span>
                                <span class="font-semibold text-rose-600">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada invoice overdue. Semua tagihan masih dalam termin atau sudah lunas.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="border-b border-slate-100 pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Eksposur Pelanggan</p>
                    <h3 class="mt-2 text-2xl font-bold text-slate-900">Top Piutang Perusahaan</h3>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($companyReceivables as $company)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $company->company_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $company->invoice_count }} invoice terbuka</p>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $company->outstanding_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada saldo piutang yang perlu dipantau.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Invoice Terbaru</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Tagihan Terakhir</h3>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($recentInvoices as $invoice)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $invoice->invoice_number }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $invoice->company?->name ?: '-' }} · {{ optional($invoice->invoice_date)->format('d M Y') }}</p>
                                </div>
                                <span class="text-sm font-semibold text-slate-900">Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">{{ $invoice->statusLabel() }} · Sisa Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada invoice yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pembayaran Terbaru</p>
                        <h3 class="mt-2 text-2xl font-bold text-slate-900">Arus Kas Masuk</h3>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($recentPayments as $payment)
                        <div class="rounded-[1.5rem] border border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $payment->payment_number }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $payment->invoice?->invoice_number ?: '-' }} · {{ optional($payment->payment_date)->format('d M Y') }}</p>
                                </div>
                                <span class="text-sm font-semibold text-emerald-700">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">{{ $payment->invoice?->company?->name ?: '-' }} · {{ ucfirst($payment->payment_method) }}</p>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada pembayaran piutang yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
