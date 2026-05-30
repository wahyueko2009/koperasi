<?php

namespace App\Http\Controllers;

use App\Models\PiutangUsahaCompany;
use App\Models\PiutangUsahaContract;
use App\Models\PiutangUsahaContractItem;
use App\Models\PiutangUsahaInvoice;
use App\Models\PiutangUsahaInvoiceItem;
use App\Models\PiutangUsahaPayment;
use App\Services\JournalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiutangUsahaController extends Controller
{
    public function __construct(
        private JournalService $journalService
    ) {}

    public function dashboard(): View
    {
        $invoices = PiutangUsahaInvoice::query()
            ->with(['company', 'contract', 'payments'])
            ->latest('invoice_date')
            ->latest('id')
            ->get();
        $payments = PiutangUsahaPayment::query()
            ->with(['invoice.company'])
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $openInvoices = $invoices->filter(fn (PiutangUsahaInvoice $invoice) => (float) $invoice->outstanding_amount > 0)->values();
        $overdueInvoices = $openInvoices->filter(fn (PiutangUsahaInvoice $invoice) => $invoice->isOverdue())->values();
        $currentMonthStart = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->endOfMonth()->toDateString();

        $companyReceivables = $openInvoices
            ->groupBy('company_id')
            ->map(function ($items) {
                $company = $items->first()->company;

                return (object) [
                    'company_name' => $company?->name ?: '-',
                    'invoice_count' => $items->count(),
                    'outstanding_amount' => $items->sum(fn ($invoice) => (float) $invoice->outstanding_amount),
                ];
            })
            ->sortByDesc('outstanding_amount')
            ->take(5)
            ->values();

        return view('piutang-usaha.dashboard', [
            'title' => 'Dashboard Piutang Usaha - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Dashboard Piutang Usaha',
            'pageDescription' => 'Pantau ringkasan tagihan jasa perusahaan, invoice aktif, pembayaran masuk, dan piutang yang perlu ditindaklanjuti.',
            'headerTabs' => collect($this->sections())->map(fn (array $section) => [
                'route' => $section['route'],
                'label' => $section['label'],
                'active' => $section['route'] === 'piutang-usaha.dashboard',
            ])->all(),
            'activeCompanyCount' => PiutangUsahaCompany::query()->where('is_active', true)->count(),
            'activeContractCount' => PiutangUsahaContract::query()->where('status', 'active')->count(),
            'openInvoices' => $openInvoices,
            'overdueInvoices' => $overdueInvoices,
            'billedThisMonth' => $invoices
                ->whereBetween('invoice_date', [$currentMonthStart, $currentMonthEnd])
                ->sum('total_amount'),
            'receivedThisMonth' => $payments
                ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount'),
            'companyReceivables' => $companyReceivables,
            'recentInvoices' => $invoices->take(5),
            'recentPayments' => $payments->take(5),
        ]);
    }

    public function companies(): View
    {
        $companies = PiutangUsahaCompany::query()->latest()->get();

        return view('piutang-usaha.companies', [
            'title' => 'Data Perusahaan - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Data Perusahaan',
            'pageDescription' => 'Kelola master perusahaan pelanggan, PIC penagihan, termin pembayaran, dan identitas bisnis yang dipakai pada kontrak serta invoice.',
            'headerTabs' => collect($this->sections())
                ->map(fn (array $section) => [
                    'route' => $section['route'],
                    'label' => $section['label'],
                    'active' => $section['route'] === 'piutang-usaha.companies',
                ])->all(),
            'companies' => $companies,
            'companyCode' => $this->generateCompanyCode(),
        ]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:piutang_usaha_companies,code'],
            'name' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'npwp' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PiutangUsahaCompany::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'pic_name' => $validated['pic_name'] ?? null,
            'billing_email' => $validated['billing_email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'npwp' => $validated['npwp'] ?? null,
            'payment_term_days' => $validated['payment_term_days'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('piutang-usaha.companies')
            ->with('success', 'Data perusahaan berhasil ditambahkan.');
    }

    public function contracts(): View
    {
        return view('piutang-usaha.contracts', [
            'title' => 'Kontrak Jasa - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Kontrak Jasa',
            'pageDescription' => 'Catat kontrak kerja sama jasa dengan perusahaan, periode layanan, komponen tarif, dan status kontrak aktif sebagai dasar penagihan bulanan.',
            'headerTabs' => collect($this->sections())
                ->map(fn (array $section) => [
                    'route' => $section['route'],
                    'label' => $section['label'],
                    'active' => $section['route'] === 'piutang-usaha.contracts',
                ])->all(),
            'companies' => PiutangUsahaCompany::query()->where('is_active', true)->orderBy('name')->get(),
            'contracts' => PiutangUsahaContract::query()->with(['company', 'items'])->latest('contract_date')->latest('id')->get(),
            'contractNumber' => $this->generateContractNumber(),
        ]);
    }

    public function storeContract(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contract_number' => ['required', 'string', 'max:100', 'unique:piutang_usaha_contracts,contract_number'],
            'company_id' => ['required', 'exists:piutang_usaha_companies,id'],
            'contract_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'service_category' => ['required', 'string', 'max:255'],
            'billing_cycle' => ['required', 'in:harian,mingguan,bulanan'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'status' => ['required', 'in:draft,active,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['required', 'string', 'max:100'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $contract = PiutangUsahaContract::create([
                'contract_number' => $validated['contract_number'],
                'company_id' => $validated['company_id'],
                'contract_date' => $validated['contract_date'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'service_category' => $validated['service_category'],
                'billing_cycle' => $validated['billing_cycle'],
                'payment_term_days' => $validated['payment_term_days'],
                'total_amount' => 0,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $subtotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $totalAmount += $subtotal;

                PiutangUsahaContractItem::create([
                    'contract_id' => $contract->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $contract->update([
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('piutang-usaha.contracts')
            ->with('success', 'Kontrak jasa berhasil ditambahkan.');
    }

    public function invoices(): View
    {
        $contracts = PiutangUsahaContract::query()
            ->with(['company', 'items'])
            ->where('status', 'active')
            ->latest('contract_date')
            ->latest('id')
            ->get();

        $contractsJson = json_encode(
            $contracts->map(fn (PiutangUsahaContract $contract) => [
                'id' => $contract->id,
                'company_name' => $contract->company?->name,
                'service_category' => $contract->service_category,
                'billing_cycle_label' => ucfirst($contract->billing_cycle),
                'payment_term_days' => (int) $contract->payment_term_days,
                'total_amount' => (float) $contract->total_amount,
                'period_label' => trim(
                    optional($contract->start_date)->format('d M Y')
                    . ($contract->end_date ? ' - ' . optional($contract->end_date)->format('d M Y') : ' - Berjalan')
                ),
                'items' => $contract->items->map(fn (PiutangUsahaContractItem $item) => [
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ])->values()->all(),
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return view('piutang-usaha.invoices', [
            'title' => 'Tagihan / Invoice - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Tagihan / Invoice',
            'pageDescription' => 'Kelola invoice tagihan bulanan, periode layanan, lampiran pendukung, jatuh tempo, dan status penagihan ke perusahaan pelanggan.',
            'headerTabs' => collect($this->sections())
                ->map(fn (array $section) => [
                    'route' => $section['route'],
                    'label' => $section['label'],
                    'active' => $section['route'] === 'piutang-usaha.invoices',
                ])->all(),
            'contracts' => $contracts,
            'contractsJson' => $contractsJson,
            'invoices' => PiutangUsahaInvoice::query()->with(['company', 'contract'])->latest('invoice_date')->latest('id')->get(),
            'invoiceNumber' => $this->generateInvoiceNumber(),
        ]);
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:100', 'unique:piutang_usaha_invoices,invoice_number'],
            'contract_id' => ['required', 'exists:piutang_usaha_contracts,id'],
            'invoice_date' => ['required', 'date'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due_date' => ['required', 'date', 'after_or_equal:invoice_date'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'status' => ['required', 'in:draft,issued'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $contract = PiutangUsahaContract::query()
                ->with(['company', 'items'])
                ->findOrFail($validated['contract_id']);

            $totalAmount = $contract->items->sum(fn (PiutangUsahaContractItem $item) => (float) $item->subtotal);

            $invoice = PiutangUsahaInvoice::create([
                'invoice_number' => $validated['invoice_number'],
                'contract_id' => $contract->id,
                'company_id' => $contract->company_id,
                'invoice_date' => $validated['invoice_date'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'due_date' => $validated['due_date'],
                'payment_term_days' => $validated['payment_term_days'],
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'outstanding_amount' => $totalAmount,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'journal_entry_id' => null,
            ]);

            foreach ($contract->items as $item) {
                PiutangUsahaInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'contract_item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            if ($invoice->status === 'issued') {
                $journal = $this->journalService->createMappedJournal(
                    sourceModule: 'piutang-usaha',
                    transactionType: 'invoice-terbit',
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                    amount: $totalAmount,
                    memo: 'Invoice ' . $invoice->invoice_number . ' - ' . ($contract->company?->name ?: 'Perusahaan')
                );

                $invoice->update([
                    'journal_entry_id' => $journal->id,
                ]);
            }
        });

        return redirect()
            ->route('piutang-usaha.invoices')
            ->with('success', 'Tagihan / invoice berhasil ditambahkan.');
    }

    public function payments(): View
    {
        $invoices = PiutangUsahaInvoice::query()
            ->with(['company', 'contract'])
            ->whereIn('status', ['issued', 'partial'])
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();

        $invoicesJson = json_encode(
            $invoices->map(fn (PiutangUsahaInvoice $invoice) => [
                'id' => $invoice->id,
                'company_name' => $invoice->company?->name,
                'due_date_label' => optional($invoice->due_date)->format('d M Y'),
                'status_label' => $invoice->statusLabel(),
                'is_overdue' => $invoice->isOverdue(),
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'outstanding_amount' => (float) $invoice->outstanding_amount,
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return view('piutang-usaha.payments', [
            'title' => 'Pembayaran Piutang - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Pembayaran Piutang',
            'pageDescription' => 'Catat pembayaran cicilan maupun pelunasan invoice, simpan referensi transfer, dan pantau sisa piutang per pelanggan.',
            'headerTabs' => collect($this->sections())
                ->map(fn (array $section) => [
                    'route' => $section['route'],
                    'label' => $section['label'],
                    'active' => $section['route'] === 'piutang-usaha.payments',
                ])->all(),
            'invoices' => $invoices,
            'invoicesJson' => $invoicesJson,
            'payments' => PiutangUsahaPayment::query()->with(['invoice.company'])->latest('payment_date')->latest('id')->get(),
            'paymentNumber' => $this->generatePaymentNumber(),
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_number' => ['required', 'string', 'max:100', 'unique:piutang_usaha_payments,payment_number'],
            'invoice_id' => ['required', 'exists:piutang_usaha_invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:transfer,giro,cash,lainnya'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = PiutangUsahaInvoice::query()->lockForUpdate()->findOrFail($validated['invoice_id']);
            $amount = (float) $validated['amount'];
            $currentOutstanding = (float) $invoice->outstanding_amount;
            $paymentMethodTransaction = $validated['payment_method'] === 'cash'
                ? 'pembayaran-piutang-kas'
                : 'pembayaran-piutang-bank';

            if ($invoice->status === 'draft') {
                abort(422, 'Invoice draft belum bisa menerima pembayaran.');
            }

            if ($currentOutstanding <= 0) {
                abort(422, 'Invoice ini sudah lunas.');
            }

            if ($amount > $currentOutstanding) {
                abort(422, 'Nominal pembayaran melebihi sisa piutang invoice.');
            }

            $payment = PiutangUsahaPayment::create([
                'invoice_id' => $invoice->id,
                'payment_number' => $validated['payment_number'],
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'journal_entry_id' => null,
            ]);

            $paidAmount = (float) $invoice->paid_amount + $amount;
            $outstandingAmount = max(0, (float) $invoice->total_amount - $paidAmount);
            $status = $outstandingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'issued');

            $invoice->update([
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'status' => $status,
            ]);

            $journal = $this->journalService->createMappedJournal(
                sourceModule: 'piutang-usaha',
                transactionType: $paymentMethodTransaction,
                referenceType: 'invoice_payment',
                referenceId: $payment->id,
                amount: $amount,
                memo: 'Pembayaran invoice ' . $invoice->invoice_number . ' - ' . ($invoice->company?->name ?: 'Perusahaan')
            );

            $payment->update([
                'journal_entry_id' => $journal->id,
            ]);
        });

        return redirect()
            ->route('piutang-usaha.payments')
            ->with('success', 'Pembayaran piutang berhasil disimpan.');
    }

    public function reports(): View
    {
        $invoices = PiutangUsahaInvoice::query()
            ->with(['company', 'contract', 'payments'])
            ->latest('invoice_date')
            ->latest('id')
            ->get();
        $payments = PiutangUsahaPayment::query()
            ->with(['invoice.company'])
            ->latest('payment_date')
            ->latest('id')
            ->get();
        $openInvoices = $invoices->filter(fn (PiutangUsahaInvoice $invoice) => (float) $invoice->outstanding_amount > 0)->values();

        $aging = [
            'current' => 0,
            'overdue_1_30' => 0,
            'overdue_31_60' => 0,
            'overdue_61_90' => 0,
            'overdue_90_plus' => 0,
        ];

        foreach ($openInvoices as $invoice) {
            if (!$invoice->due_date || !$invoice->isOverdue()) {
                $aging['current'] += (float) $invoice->outstanding_amount;
                continue;
            }

            $days = (int) $invoice->due_date->diffInDays(now());
            if ($days <= 30) {
                $aging['overdue_1_30'] += (float) $invoice->outstanding_amount;
            } elseif ($days <= 60) {
                $aging['overdue_31_60'] += (float) $invoice->outstanding_amount;
            } elseif ($days <= 90) {
                $aging['overdue_61_90'] += (float) $invoice->outstanding_amount;
            } else {
                $aging['overdue_90_plus'] += (float) $invoice->outstanding_amount;
            }
        }

        $topDebtors = $openInvoices
            ->groupBy('company_id')
            ->map(function ($items) {
                $company = $items->first()->company;

                return (object) [
                    'company_name' => $company?->name ?: '-',
                    'invoice_count' => $items->count(),
                    'outstanding_amount' => $items->sum(fn ($invoice) => (float) $invoice->outstanding_amount),
                ];
            })
            ->sortByDesc('outstanding_amount')
            ->take(10)
            ->values();

        return view('piutang-usaha.reports', [
            'title' => 'Laporan Piutang Usaha - Koperasi Digital Mandiri',
            'sectionLabel' => 'Piutang Usaha',
            'pageTitle' => 'Laporan Piutang Usaha',
            'pageDescription' => 'Akses laporan saldo piutang, invoice jatuh tempo, histori pembayaran, dan umur piutang untuk kebutuhan operasional serta monitoring manajemen.',
            'headerTabs' => collect($this->sections())->map(fn (array $section) => [
                'route' => $section['route'],
                'label' => $section['label'],
                'active' => $section['route'] === 'piutang-usaha.reports',
            ])->all(),
            'invoices' => $invoices,
            'payments' => $payments,
            'openInvoices' => $openInvoices,
            'aging' => $aging,
            'topDebtors' => $topDebtors,
            'totalOutstanding' => $openInvoices->sum('outstanding_amount'),
            'totalBilled' => $invoices->sum('total_amount'),
            'totalPaid' => $payments->sum('amount'),
            'overdueTotal' => $openInvoices->filter(fn (PiutangUsahaInvoice $invoice) => $invoice->isOverdue())->sum('outstanding_amount'),
        ]);
    }

    private function renderSection(
        string $routeName,
        string $sectionLabel,
        string $pageTitle,
        string $pageDescription
    ): View {
        $sections = $this->sections();

        return view('piutang-usaha.section', [
            'title' => $pageTitle . ' - Koperasi Digital Mandiri',
            'sectionLabel' => $sectionLabel,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'headerTabs' => collect($sections)
                ->map(fn (array $section) => [
                    'route' => $section['route'],
                    'label' => $section['label'],
                    'active' => $section['route'] === $routeName,
                ])->all(),
            'currentRouteName' => $routeName,
            'sections' => $sections,
        ]);
    }

    private function sections(): array
    {
        return [
            [
                'route' => 'piutang-usaha.dashboard',
                'label' => 'Dashboard',
                'icon' => 'fas fa-chart-line',
                'summary' => 'Ringkasan invoice aktif, piutang berjalan, dan tindak lanjut tagihan.',
            ],
            [
                'route' => 'piutang-usaha.companies',
                'label' => 'Data Perusahaan',
                'icon' => 'fas fa-building',
                'summary' => 'Master pelanggan perusahaan, PIC, kontak, dan termin pembayaran.',
            ],
            [
                'route' => 'piutang-usaha.contracts',
                'label' => 'Kontrak Jasa',
                'icon' => 'fas fa-file-signature',
                'summary' => 'Kontrak kerja sama jasa, periode layanan, dan komponen tagihan.',
            ],
            [
                'route' => 'piutang-usaha.invoices',
                'label' => 'Tagihan / Invoice',
                'icon' => 'fas fa-file-invoice-dollar',
                'summary' => 'Pembuatan invoice bulanan, lampiran pendukung, dan jatuh tempo.',
            ],
            [
                'route' => 'piutang-usaha.payments',
                'label' => 'Pembayaran Piutang',
                'icon' => 'fas fa-wallet',
                'summary' => 'Pencatatan pembayaran cicilan dan pelunasan invoice pelanggan.',
            ],
            [
                'route' => 'piutang-usaha.reports',
                'label' => 'Laporan',
                'icon' => 'fas fa-file-lines',
                'summary' => 'Laporan saldo piutang, umur piutang, dan histori pembayaran.',
            ],
        ];
    }

    private function generateCompanyCode(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = PiutangUsahaCompany::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'CUS-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    private function generateContractNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = PiutangUsahaContract::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'CTR-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    private function generateInvoiceNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = PiutangUsahaInvoice::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'INV-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    private function generatePaymentNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = PiutangUsahaPayment::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'PAY-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }
}
