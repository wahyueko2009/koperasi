<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\GeneralLedger;
use App\Models\GlAccountGroup;
use App\Models\GlAccountMapping;
use App\Models\GlCostCenter;
use App\Models\GlPeriod;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class GeneralLedgerController extends Controller
{
    public function __construct(
        private JournalService $journalService
    ) {}

    public function dashboard(): View
    {
        $openPeriod = GlPeriod::query()->where('status', 'open')->orderByDesc('start_date')->first();
        $recentJournals = JournalEntry::query()
            ->with(['creator', 'poster', 'debitAccount', 'creditAccount', 'period', 'costCenter'])
            ->latest('entry_date')
            ->latest('id')
            ->take(5)
            ->get();
        $this->annotateLegacyFlags($recentJournals);
        $recentLedgers = GeneralLedger::query()
            ->with(['account', 'journalEntry'])
            ->latest('entry_date')
            ->latest('id')
            ->take(8)
            ->get();
        $totals = GeneralLedger::query()
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return view('gl.dashboard', [
            'title' => 'Dashboard GL - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Dashboard GL',
            'pageDescription' => 'Pantau ringkasan jurnal, periode aktif, saldo akun utama, dan pekerjaan akuntansi yang perlu segera diposting.',
            'headerTabs' => $this->headerTabs('gl.dashboard'),
            'openPeriod' => $openPeriod,
            'accountCount' => ChartOfAccount::query()->count(),
            'activeAccountCount' => ChartOfAccount::query()->where('is_active', true)->count(),
            'draftJournalCount' => JournalEntry::query()->where('status', 'draft')->count(),
            'postedJournalCount' => JournalEntry::query()->where('status', 'posted')->count(),
            'legacyJournalCount' => JournalEntry::query()
                ->get()
                ->filter(fn (JournalEntry $journal) => $this->isLegacyJournal($journal))
                ->count(),
            'mappingCount' => GlAccountMapping::query()->where('is_active', true)->count(),
            'costCenterCount' => GlCostCenter::query()->where('is_active', true)->count(),
            'trialBalanceGap' => abs((float) ($totals->total_debit ?? 0) - (float) ($totals->total_credit ?? 0)),
            'assetBalance' => $this->balanceByType('asset'),
            'liabilityBalance' => $this->balanceByType('liability'),
            'incomeBalance' => $this->balanceByType('income'),
            'expenseBalance' => $this->balanceByType('expense'),
            'recentJournals' => $recentJournals,
            'recentLedgers' => $recentLedgers,
        ]);
    }

    public function setupDefaults(): RedirectResponse
    {
        DB::transaction(fn () => $this->seedDefaultSetup());

        return redirect()->route('gl.dashboard')->with('success', 'Data awal GL berhasil disiapkan: kelompok akun, COA standar, dan periode aktif.');
    }

    public function setupDefaultMappings(): RedirectResponse
    {
        $this->seedDefaultMappings();

        return redirect()->route('gl.mappings')->with('success', 'Mapping akun default berhasil disiapkan.');
    }

    public function setupSampleJournals(): RedirectResponse
    {
        DB::transaction(function () {
            $this->seedDefaultSetup();
            $this->seedSampleJournals();
        });

        return redirect()->route('gl.journals')->with('success', 'Jurnal contoh berhasil disiapkan untuk kebutuhan review GL.');
    }

    public function accounts(): View
    {
        $accounts = ChartOfAccount::query()
            ->with(['parent', 'accountGroup', 'children'])
            ->orderBy('code')
            ->get();
        $flattenedAccounts = $this->flattenAccountsForDisplay($accounts);

        return view('gl.accounts', [
            'title' => 'Daftar Akun / COA - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Daftar Akun / COA',
            'pageDescription' => 'Kelola chart of accounts sebagai pondasi pencatatan seluruh transaksi koperasi, baik manual maupun dari modul operasional.',
            'headerTabs' => $this->headerTabs('gl.accounts'),
            'accounts' => $flattenedAccounts,
            'parentAccounts' => $flattenedAccounts->where('is_active', true)->values(),
            'accountGroups' => GlAccountGroup::query()->where('is_active', true)->orderBy('code')->get(),
            'accountCode' => $this->generateAccountCode(),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:chart_of_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:asset,liability,equity,income,expense'],
            'account_group_id' => ['nullable', 'exists:gl_account_groups,id'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'is_header' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ChartOfAccount::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'account_group_id' => $validated['account_group_id'] ?? null,
            'normal_balance' => $validated['normal_balance'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_header' => (bool) ($validated['is_header'] ?? false),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('gl.accounts')->with('success', 'Akun / COA berhasil ditambahkan.');
    }

    public function updateAccount(Request $request, ChartOfAccount $account): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('chart_of_accounts', 'code')->ignore($account->id)],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:asset,liability,equity,income,expense'],
            'account_group_id' => ['nullable', 'exists:gl_account_groups,id'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'is_header' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parentId = (int) $validated['parent_id'];

            if ($parentId === (int) $account->id) {
                return back()->withInput()->withErrors([
                    'parent_id' => 'Akun tidak bisa menjadi parent untuk dirinya sendiri.',
                ]);
            }

            $descendantIds = $this->descendantIds($account);
            if (in_array($parentId, $descendantIds, true)) {
                return back()->withInput()->withErrors([
                    'parent_id' => 'Parent account tidak boleh berasal dari turunan akun ini.',
                ]);
            }
        }

        $account->update([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'account_group_id' => $validated['account_group_id'] ?? null,
            'normal_balance' => $validated['normal_balance'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_header' => (bool) ($validated['is_header'] ?? false),
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('gl.accounts')->with('success', 'Akun / COA berhasil diperbarui.');
    }

    public function accountGroups(): View
    {
        $groups = GlAccountGroup::query()->withCount('accounts')->orderBy('code')->get();

        return view('gl.account-groups', [
            'title' => 'Kelompok Akun - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Kelompok Akun',
            'pageDescription' => 'Susun struktur kelompok akun agar klasifikasi aset, kewajiban, modal, pendapatan, dan beban tetap rapi serta mudah dilaporkan.',
            'headerTabs' => $this->headerTabs('gl.account-groups'),
            'groups' => $groups,
            'groupCode' => $this->generateGroupCode(),
        ]);
    }

    public function storeAccountGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:gl_account_groups,code'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:asset,liability,equity,income,expense'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        GlAccountGroup::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'account_type' => $validated['account_type'],
            'normal_balance' => $validated['normal_balance'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('gl.account-groups')->with('success', 'Kelompok akun berhasil ditambahkan.');
    }

    public function periods(): View
    {
        $periods = GlPeriod::query()->latest('start_date')->latest('id')->get();

        return view('gl.periods', [
            'title' => 'Periode Akuntansi - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Periode Akuntansi',
            'pageDescription' => 'Atur periode buka dan tutup buku supaya pencatatan jurnal, posting, dan pelaporan berjalan tertib per bulan akuntansi.',
            'headerTabs' => $this->headerTabs('gl.periods'),
            'periods' => $periods,
            'periodCode' => now()->format('Ym'),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:gl_periods,code'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,open,closed'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            if ($validated['status'] === 'open') {
                $this->ensureSingleOpenPeriod();
            }

            GlPeriod::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'closed_at' => $validated['status'] === 'closed' ? now() : null,
            ]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('gl.periods')->with('success', 'Periode akuntansi berhasil ditambahkan.');
    }

    public function journals(): View
    {
        $journals = JournalEntry::query()
            ->with(['creator', 'poster', 'debitAccount', 'creditAccount', 'period', 'costCenter'])
            ->latest('entry_date')
            ->latest('id')
            ->get();
        $this->annotateLegacyFlags($journals);

        return view('gl.journals', [
            'title' => 'Jurnal Umum - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Jurnal Umum',
            'pageDescription' => 'Gunakan jurnal umum untuk penyesuaian, memorial, koreksi, dan transaksi manual yang belum berasal dari modul lain.',
            'headerTabs' => $this->headerTabs('gl.journals'),
            'journals' => $journals,
            'accounts' => ChartOfAccount::query()->where('is_active', true)->where('is_header', false)->orderBy('code')->get(),
            'periods' => GlPeriod::query()->whereIn('status', ['draft', 'open'])->orderByDesc('start_date')->get(),
            'costCenters' => GlCostCenter::query()->where('is_active', true)->orderBy('code')->get(),
            'referenceNumber' => $this->generateJournalReference(),
        ]);
    }

    public function storeJournal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'period_id' => ['nullable', 'exists:gl_periods,id'],
            'cost_center_id' => ['nullable', 'exists:gl_cost_centers,id'],
            'reference_number' => ['required', 'string', 'max:100'],
            'debit_account_id' => ['required', 'different:credit_account_id', 'exists:chart_of_accounts,id'],
            'credit_account_id' => ['required', 'different:debit_account_id', 'exists:chart_of_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,posted'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $this->journalService->createManualJournal(
                    attributes: $validated,
                    postImmediately: $validated['status'] === 'posted',
                );
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors([
                'entry_date' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('gl.journals')->with('success', 'Jurnal umum berhasil disimpan.');
    }

    public function posting(): View
    {
        $draftJournals = JournalEntry::query()
            ->with(['creator', 'poster', 'debitAccount', 'creditAccount', 'period', 'costCenter'])
            ->where('status', 'draft')
            ->latest('entry_date')
            ->latest('id')
            ->get();
        $postedJournals = JournalEntry::query()
            ->with(['creator', 'poster', 'debitAccount', 'creditAccount', 'period', 'costCenter'])
            ->where('status', 'posted')
            ->latest('posted_at')
            ->latest('id')
            ->take(10)
            ->get();
        $this->annotateLegacyFlags($draftJournals);
        $this->annotateLegacyFlags($postedJournals);

        return view('gl.posting', [
            'title' => 'Posting Jurnal - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Posting Jurnal',
            'pageDescription' => 'Review dan posting jurnal ke buku besar agar transaksi dari modul operasional maupun jurnal manual masuk ke saldo akun resmi.',
            'headerTabs' => $this->headerTabs('gl.posting'),
            'draftJournals' => $draftJournals,
            'postedJournals' => $postedJournals,
        ]);
    }

    public function processPosting(JournalEntry $journal): RedirectResponse
    {
        if ($journal->status === 'posted') {
            return redirect()->route('gl.posting')->with('success', 'Jurnal tersebut sudah diposting sebelumnya.');
        }

        try {
            DB::transaction(fn () => $this->journalService->postExistingJournal($journal));
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'posting' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('gl.posting')->with('success', 'Jurnal berhasil diposting ke buku besar.');
    }

    public function mappings(): View
    {
        $mappings = GlAccountMapping::query()
            ->with(['debitAccount', 'creditAccount'])
            ->latest()
            ->get();

        return view('gl.mappings', [
            'title' => 'Mapping Akun - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Mapping Akun',
            'pageDescription' => 'Hubungkan transaksi dari kasir, piutang usaha, fixed aset, dan modul lain ke akun GL yang benar agar tidak perlu entry ulang.',
            'headerTabs' => $this->headerTabs('gl.mappings'),
            'mappings' => $mappings,
            'accounts' => ChartOfAccount::query()->where('is_active', true)->where('is_header', false)->orderBy('code')->get(),
        ]);
    }

    public function storeMapping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_module' => ['required', 'string', 'max:100'],
            'transaction_type' => ['required', 'string', 'max:100'],
            'debit_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'credit_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        GlAccountMapping::create([
            'source_module' => $validated['source_module'],
            'transaction_type' => $validated['transaction_type'],
            'debit_account_id' => $validated['debit_account_id'] ?? null,
            'credit_account_id' => $validated['credit_account_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('gl.mappings')->with('success', 'Mapping akun berhasil ditambahkan.');
    }

    public function costCenters(): View
    {
        $costCenters = GlCostCenter::query()->latest()->get();

        return view('gl.cost-centers', [
            'title' => 'Pusat Biaya - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Pusat Biaya',
            'pageDescription' => 'Kelola pusat biaya untuk pelacakan biaya per unit, divisi, proyek, kontrak, atau aktivitas usaha koperasi.',
            'headerTabs' => $this->headerTabs('gl.cost-centers'),
            'costCenters' => $costCenters,
            'costCenterCode' => $this->generateCostCenterCode(),
        ]);
    }

    public function storeCostCenter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:gl_cost_centers,code'],
            'name' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        GlCostCenter::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'pic_name' => $validated['pic_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('gl.cost-centers')->with('success', 'Pusat biaya berhasil ditambahkan.');
    }

    public function ledgers(Request $request): View
    {
        $query = GeneralLedger::query()->with(['account', 'journalEntry']);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->integer('account_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date('date_to'));
        }

        $entries = $query->orderBy('entry_date')->orderBy('id')->get();

        return view('gl.ledgers', [
            'title' => 'Buku Besar - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Buku Besar',
            'pageDescription' => 'Lihat mutasi lengkap setiap akun beserta saldo berjalan sebagai dasar pemeriksaan transaksi dan penyusunan laporan.',
            'headerTabs' => $this->headerTabs('gl.ledgers'),
            'entries' => $entries,
            'accounts' => ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get(),
            'filters' => $request->only(['account_id', 'date_from', 'date_to']),
            'totalDebit' => $entries->sum('debit'),
            'totalCredit' => $entries->sum('credit'),
        ]);
    }

    public function trialBalance(Request $request): View
    {
        $rows = $this->trialBalanceRows($request->input('date_to'));

        return view('gl.trial-balance', [
            'title' => 'Neraca Saldo - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Neraca Saldo',
            'pageDescription' => 'Cek keseimbangan debit dan kredit seluruh akun sebelum laporan keuangan diterbitkan atau periode ditutup.',
            'headerTabs' => $this->headerTabs('gl.trial-balance'),
            'rows' => $rows,
            'dateTo' => $request->input('date_to'),
            'totalDebit' => $rows->sum('debit_balance'),
            'totalCredit' => $rows->sum('credit_balance'),
        ]);
    }

    public function financialReports(Request $request): View
    {
        $rows = $this->trialBalanceRows($request->input('date_to'));
        $grouped = $rows->groupBy('account_type');
        $income = $grouped->get('income', collect())->sum('net_balance');
        $expense = $grouped->get('expense', collect())->sum('net_balance');

        return view('gl.financial-reports', [
            'title' => 'Laporan Keuangan - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Laporan Keuangan',
            'pageDescription' => 'Siapkan laba rugi, neraca, arus kas, dan laporan penting lain dari data jurnal yang sudah diposting.',
            'headerTabs' => $this->headerTabs('gl.financial-reports'),
            'dateTo' => $request->input('date_to'),
            'assets' => $grouped->get('asset', collect()),
            'liabilities' => $grouped->get('liability', collect()),
            'equities' => $grouped->get('equity', collect()),
            'incomes' => $grouped->get('income', collect()),
            'expenses' => $grouped->get('expense', collect()),
            'incomeTotal' => $income,
            'expenseTotal' => $expense,
            'netIncome' => $income - $expense,
        ]);
    }

    public function closing(): View
    {
        return view('gl.closing', [
            'title' => 'Tutup Buku - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Tutup Buku',
            'pageDescription' => 'Kelola proses tutup buku akhir periode agar saldo pembuka dan kunci transaksi akuntansi tetap terjaga dengan baik.',
            'headerTabs' => $this->headerTabs('gl.closing'),
            'openPeriods' => GlPeriod::query()->whereIn('status', ['draft', 'open'])->orderBy('start_date')->get(),
            'closedPeriods' => GlPeriod::query()->with('closer')->where('status', 'closed')->latest('closed_at')->take(10)->get(),
        ]);
    }

    public function processClosing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_id' => ['required', 'exists:gl_periods,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $period = GlPeriod::query()->findOrFail($validated['period_id']);
        if ($period->status !== 'open') {
            return back()->withErrors([
                'period_id' => 'Hanya periode dengan status open yang bisa ditutup.',
            ]);
        }

        if ($period->journals()->where('status', 'draft')->exists()) {
            return back()->withErrors([
                'period_id' => 'Periode ini masih memiliki jurnal draft yang belum diposting.',
            ]);
        }

        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'notes' => $validated['notes'] ?? $period->notes,
        ]);

        return redirect()->route('gl.closing')->with('success', 'Periode akuntansi berhasil ditutup.');
    }

    public function syncLegacyData(): RedirectResponse
    {
        $syncedJournalCount = 0;
        $deactivatedAccountCount = 0;
        $deactivatedMappingCount = 0;

        DB::transaction(function () use (&$syncedJournalCount, &$deactivatedAccountCount, &$deactivatedMappingCount) {
            $period = GlPeriod::query()
                ->where('status', 'open')
                ->orderByDesc('start_date')
                ->firstOrFail();

            $legacyJournals = JournalEntry::query()
                ->whereNull('period_id')
                ->orderBy('id')
                ->get();

            foreach ($legacyJournals as $index => $journal) {
                $journal->update([
                    'period_id' => $period->id,
                    'reference_number' => $journal->reference_number ?: 'LEGACY-JRN-' . $period->code . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ]);
            }

            $syncedJournalCount = $legacyJournals->count();

            $inactiveCodes = ['2002', '2102', '3101', '3102', '4001', '4002', '5103', '5104'];
            $deactivatedAccountCount = ChartOfAccount::query()
                ->whereIn('code', $inactiveCodes)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $deactivatedMappingCount = GlAccountMapping::query()
                ->where('source_module', 'unit-usaha')
                ->where('transaction_type', 'penjualan-tunai')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $oldIncome = ChartOfAccount::query()->where('code', '4002')->first();
            $newIncome = ChartOfAccount::query()->where('code', '4104')->first();

            if ($oldIncome && $newIncome) {
                $journal = JournalEntry::query()
                    ->where('reference_number', 'SAMPLE-GL-003')
                    ->where('credit_account_id', $oldIncome->id)
                    ->first();

                if ($journal) {
                    $journal->update(['credit_account_id' => $newIncome->id]);

                    GeneralLedger::query()
                        ->where('journal_entry_id', $journal->id)
                        ->where('account_id', $oldIncome->id)
                        ->update(['account_id' => $newIncome->id]);
                }
            }
        });

        return redirect()
            ->route('gl.dashboard')
            ->with('success', "Sinkronisasi legacy selesai. Jurnal diperbarui: {$syncedJournalCount}, akun dinonaktifkan: {$deactivatedAccountCount}, mapping dinonaktifkan: {$deactivatedMappingCount}.");
    }

    public function audit(Request $request): View
    {
        $query = JournalEntry::query()->with(['creator', 'poster', 'debitAccount', 'creditAccount', 'period', 'costCenter']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('source_module')) {
            $query->where('source_module', 'like', '%' . $request->input('source_module') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('entry_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('entry_date', '<=', $request->date('date_to'));
        }

        $journals = $query->latest('entry_date')->latest('id')->get();
        $this->annotateLegacyFlags($journals);

        return view('gl.audit', [
            'title' => 'Audit Jurnal - Koperasi Digital Mandiri',
            'sectionLabel' => 'Buku Besar / GL',
            'pageTitle' => 'Audit Jurnal',
            'pageDescription' => 'Lacak perubahan, pembatalan, reposting, dan aktivitas user pada jurnal sebagai jejak audit akuntansi.',
            'headerTabs' => $this->headerTabs('gl.audit'),
            'journals' => $journals,
            'filters' => $request->only(['status', 'source_module', 'date_from', 'date_to']),
        ]);
    }

    private function headerTabs(string $routeName): array
    {
        return collect($this->sections())
            ->map(fn (array $section) => [
                'route' => $section['route'],
                'label' => $section['label'],
                'active' => $section['route'] === $routeName,
            ])->all();
    }

    private function sections(): array
    {
        return [
            ['route' => 'gl.dashboard', 'label' => 'Dashboard GL', 'icon' => 'fas fa-chart-line', 'summary' => 'Ringkasan jurnal, saldo utama, dan kontrol operasional akuntansi.'],
            ['route' => 'gl.accounts', 'label' => 'Daftar Akun / COA', 'icon' => 'fas fa-book', 'summary' => 'Master akun untuk seluruh transaksi dan pelaporan keuangan.'],
            ['route' => 'gl.account-groups', 'label' => 'Kelompok Akun', 'icon' => 'fas fa-layer-group', 'summary' => 'Struktur klasifikasi akun berdasarkan kelompok dan hierarki pelaporan.'],
            ['route' => 'gl.periods', 'label' => 'Periode Akuntansi', 'icon' => 'fas fa-calendar-days', 'summary' => 'Pengaturan periode buka, aktif, dan tutup buku akuntansi.'],
            ['route' => 'gl.journals', 'label' => 'Jurnal Umum', 'icon' => 'fas fa-pen-to-square', 'summary' => 'Pencatatan jurnal manual, koreksi, dan penyesuaian.'],
            ['route' => 'gl.posting', 'label' => 'Posting Jurnal', 'icon' => 'fas fa-share-from-square', 'summary' => 'Review dan posting jurnal ke buku besar resmi.'],
            ['route' => 'gl.mappings', 'label' => 'Mapping Akun', 'icon' => 'fas fa-link', 'summary' => 'Penghubung akun GL dengan transaksi dari modul operasional.'],
            ['route' => 'gl.cost-centers', 'label' => 'Pusat Biaya', 'icon' => 'fas fa-sitemap', 'summary' => 'Pelacakan biaya berdasarkan unit, divisi, atau proyek.'],
            ['route' => 'gl.ledgers', 'label' => 'Buku Besar', 'icon' => 'fas fa-book-open', 'summary' => 'Mutasi akun dan saldo berjalan sebagai inti GL.'],
            ['route' => 'gl.trial-balance', 'label' => 'Neraca Saldo', 'icon' => 'fas fa-scale-balanced', 'summary' => 'Pemeriksaan saldo debit dan kredit seluruh akun.'],
            ['route' => 'gl.financial-reports', 'label' => 'Laporan Keuangan', 'icon' => 'fas fa-file-invoice', 'summary' => 'Akses laporan laba rugi, neraca, arus kas, dan laporan lainnya.'],
            ['route' => 'gl.closing', 'label' => 'Tutup Buku', 'icon' => 'fas fa-lock', 'summary' => 'Proses penutupan periode akuntansi dan saldo pembuka.'],
            ['route' => 'gl.audit', 'label' => 'Audit Jurnal', 'icon' => 'fas fa-shield-halved', 'summary' => 'Jejak audit jurnal, perubahan user, dan histori koreksi.'],
        ];
    }

    private function balanceByType(string $accountType): float
    {
        $rows = $this->trialBalanceRows();

        return (float) $rows->where('account_type', $accountType)->sum('net_balance');
    }

    private function trialBalanceRows(?string $dateTo = null): Collection
    {
        $query = GeneralLedger::query()->with('account');

        if ($dateTo) {
            $query->whereDate('entry_date', '<=', $dateTo);
        }

        return $query->get()
            ->groupBy('account_id')
            ->map(function (Collection $items) {
                $account = $items->first()->account;
                $debit = (float) $items->sum('debit');
                $credit = (float) $items->sum('credit');
                $rawBalance = $debit - $credit;
                $net = $account?->normal_balance === 'credit' ? ($credit - $debit) : $rawBalance;

                return (object) [
                    'account_id' => $account?->id,
                    'code' => $account?->code,
                    'name' => $account?->name,
                    'account_type' => $account?->account_type,
                    'normal_balance' => $account?->normal_balance,
                    'debit_balance' => max($rawBalance, 0),
                    'credit_balance' => max($credit - $debit, 0),
                    'net_balance' => $net,
                ];
            })
            ->sortBy('code')
            ->values();
    }

    private function ensureSingleOpenPeriod(?int $ignorePeriodId = null): void
    {
        $query = GlPeriod::query()->where('status', 'open');

        if ($ignorePeriodId) {
            $query->where('id', '!=', $ignorePeriodId);
        }

        if ($query->exists()) {
            throw new RuntimeException('Hanya boleh ada satu periode akuntansi dengan status open.');
        }
    }

    private function generateAccountCode(): string
    {
        $latestCode = ChartOfAccount::query()
            ->whereRaw("code GLOB '[0-9]*'")
            ->orderByRaw('LENGTH(code) DESC')
            ->orderByDesc('code')
            ->value('code');

        if (!$latestCode || !is_numeric($latestCode)) {
            return '1001';
        }

        return (string) ((int) $latestCode + 1);
    }

    private function generateGroupCode(): string
    {
        $count = GlAccountGroup::query()->count() + 1;

        return 'GRP-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    private function generateCostCenterCode(): string
    {
        $count = GlCostCenter::query()->count() + 1;

        return 'CC-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalReference(): string
    {
        $datePrefix = now()->format('Ymd');
        $count = JournalEntry::query()->whereDate('created_at', now()->toDateString())->count() + 1;

        return 'JRN-' . $datePrefix . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    private function defaultAccountGroups(): array
    {
        return [
            ['code' => 'AST-CUR', 'name' => 'Aset Lancar', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Kas, bank, piutang, persediaan, dan aset lancar lain.', 'is_active' => true],
            ['code' => 'AST-FIX', 'name' => 'Aset Tetap', 'account_type' => 'asset', 'normal_balance' => 'debit', 'description' => 'Kendaraan, peralatan, inventaris, dan aset tetap lain.', 'is_active' => true],
            ['code' => 'LIA-CUR', 'name' => 'Kewajiban Lancar', 'account_type' => 'liability', 'normal_balance' => 'credit', 'description' => 'Utang usaha, utang anggota, dan kewajiban jangka pendek.', 'is_active' => true],
            ['code' => 'EQT-CAP', 'name' => 'Modal dan Ekuitas', 'account_type' => 'equity', 'normal_balance' => 'credit', 'description' => 'Modal, saldo laba, dan ekuitas koperasi.', 'is_active' => true],
            ['code' => 'INC-OPS', 'name' => 'Pendapatan Operasional', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Pendapatan retail, jasa, dan unit usaha utama.', 'is_active' => true],
            ['code' => 'INC-OTH', 'name' => 'Pendapatan Lainnya', 'account_type' => 'income', 'normal_balance' => 'credit', 'description' => 'Pendapatan bunga dan pendapatan lain di luar operasional utama.', 'is_active' => true],
            ['code' => 'EXP-OPS', 'name' => 'Beban Operasional', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Gaji, listrik, ATK, dan biaya operasional lain.', 'is_active' => true],
            ['code' => 'EXP-DEP', 'name' => 'Beban Penyusutan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'description' => 'Beban penyusutan aset tetap.', 'is_active' => true],
        ];
    }

    private function defaultAccounts(): array
    {
        return [
            ['code' => '1001', 'name' => 'Kas', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Kas tunai koperasi.'],
            ['code' => '1002', 'name' => 'Bank', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Saldo rekening bank koperasi.'],
            ['code' => '1101', 'name' => 'Piutang Anggota', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Piutang pinjaman atau tagihan ke anggota.'],
            ['code' => '1102', 'name' => 'Piutang Usaha', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Piutang jasa atau tagihan ke perusahaan.'],
            ['code' => '1201', 'name' => 'Persediaan Barang Dagang', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Persediaan retail atau ATK untuk dijual.'],
            ['code' => '1301', 'name' => 'Uang Muka Pembelian', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-CUR', 'description' => 'Pembayaran di muka ke pemasok.'],
            ['code' => '1501', 'name' => 'Kendaraan', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-FIX', 'description' => 'Aset kendaraan koperasi.'],
            ['code' => '1502', 'name' => 'Peralatan Kantor', 'account_type' => 'asset', 'normal_balance' => 'debit', 'group_code' => 'AST-FIX', 'description' => 'Peralatan dan inventaris kantor.'],
            ['code' => '1591', 'name' => 'Akumulasi Penyusutan Kendaraan', 'account_type' => 'asset', 'normal_balance' => 'credit', 'group_code' => 'AST-FIX', 'description' => 'Akumulasi penyusutan kendaraan.'],
            ['code' => '1592', 'name' => 'Akumulasi Penyusutan Peralatan', 'account_type' => 'asset', 'normal_balance' => 'credit', 'group_code' => 'AST-FIX', 'description' => 'Akumulasi penyusutan peralatan kantor.'],
            ['code' => '2001', 'name' => 'Utang Usaha', 'account_type' => 'liability', 'normal_balance' => 'credit', 'group_code' => 'LIA-CUR', 'description' => 'Utang ke supplier atau mitra usaha.'],
            ['code' => '2002', 'name' => 'Utang Simpanan Anggota', 'account_type' => 'liability', 'normal_balance' => 'credit', 'group_code' => 'LIA-CUR', 'description' => 'Kewajiban simpanan terhadap anggota.'],
            ['code' => '2003', 'name' => 'Utang Biaya', 'account_type' => 'liability', 'normal_balance' => 'credit', 'group_code' => 'LIA-CUR', 'description' => 'Biaya yang masih harus dibayar.'],
            ['code' => '2101', 'name' => 'Utang Simpanan Anggota Legacy', 'account_type' => 'liability', 'normal_balance' => 'credit', 'group_code' => 'LIA-CUR', 'description' => 'Kode legacy untuk kewajiban simpanan anggota.'],
            ['code' => '2102', 'name' => 'Utang Pinjaman Anggota', 'account_type' => 'liability', 'normal_balance' => 'credit', 'group_code' => 'LIA-CUR', 'description' => 'Kewajiban pinjaman anggota / akun legacy.'],
            ['code' => '3001', 'name' => 'Modal Koperasi', 'account_type' => 'equity', 'normal_balance' => 'credit', 'group_code' => 'EQT-CAP', 'description' => 'Modal awal dan tambahan modal koperasi.'],
            ['code' => '3002', 'name' => 'Saldo Laba', 'account_type' => 'equity', 'normal_balance' => 'credit', 'group_code' => 'EQT-CAP', 'description' => 'Akumulasi laba ditahan koperasi.'],
            ['code' => '4001', 'name' => 'Pendapatan Retail', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OPS', 'description' => 'Pendapatan dari penjualan retail.'],
            ['code' => '4002', 'name' => 'Pendapatan Jasa', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OPS', 'description' => 'Pendapatan jasa outsourcing, driver, atau sewa kendaraan.'],
            ['code' => '4101', 'name' => 'Pendapatan Bunga', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OTH', 'description' => 'Pendapatan bunga pinjaman atau jasa keuangan.'],
            ['code' => '4102', 'name' => 'Pendapatan Indomaret', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OPS', 'description' => 'Pendapatan penjualan unit Indomaret.'],
            ['code' => '4103', 'name' => 'Pendapatan Photocopy', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OPS', 'description' => 'Pendapatan penjualan unit Photocopy.'],
            ['code' => '4104', 'name' => 'Pendapatan Outsourcing', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OPS', 'description' => 'Pendapatan jasa outsourcing dan layanan usaha.'],
            ['code' => '4105', 'name' => 'Laba Pelepasan Aset', 'account_type' => 'income', 'normal_balance' => 'credit', 'group_code' => 'INC-OTH', 'description' => 'Keuntungan dari penjualan atau penghentian aset tetap.'],
            ['code' => '5001', 'name' => 'Beban Gaji', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-OPS', 'description' => 'Beban gaji dan honorarium.'],
            ['code' => '5002', 'name' => 'Beban Operasional', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-OPS', 'description' => 'Beban listrik, air, ATK, dan operasional umum.'],
            ['code' => '5003', 'name' => 'Beban Pemeliharaan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-OPS', 'description' => 'Biaya servis, maintenance, dan perbaikan.'],
            ['code' => '5004', 'name' => 'Rugi Pelepasan Aset', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-OPS', 'description' => 'Kerugian dari penjualan atau penghentian aset tetap.'],
            ['code' => '5101', 'name' => 'Beban Penyusutan Kendaraan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-DEP', 'description' => 'Beban penyusutan kendaraan.'],
            ['code' => '5102', 'name' => 'Beban Penyusutan Peralatan', 'account_type' => 'expense', 'normal_balance' => 'debit', 'group_code' => 'EXP-DEP', 'description' => 'Beban penyusutan peralatan kantor.'],
        ];
    }

    private function seedDefaultMappings(): void
    {
        $accounts = ChartOfAccount::query()
            ->whereIn('code', [
                '1001', '1002', '1101', '1102', '1201', '1501', '1591', '2001', '2002', '2101', '4001', '4101', '4102', '4103', '4104', '5002', '5101',
            ])
            ->get()
            ->keyBy('code');

        $mappings = [
            [
                'source_module' => 'simpan-pinjam',
                'transaction_type' => 'saving-deposit',
                'debit_code' => '1001',
                'credit_code' => '2101',
                'notes' => 'Setoran simpanan anggota ke kas koperasi.',
            ],
            [
                'source_module' => 'simpan-pinjam',
                'transaction_type' => 'loan-disbursement',
                'debit_code' => '1101',
                'credit_code' => '1001',
                'notes' => 'Pencairan pinjaman anggota dari kas koperasi.',
            ],
            [
                'source_module' => 'simpan-pinjam',
                'transaction_type' => 'loan-payment-principal',
                'debit_code' => '1001',
                'credit_code' => '1101',
                'notes' => 'Pembayaran pokok pinjaman anggota.',
            ],
            [
                'source_module' => 'simpan-pinjam',
                'transaction_type' => 'loan-payment-interest',
                'debit_code' => '1001',
                'credit_code' => '4101',
                'notes' => 'Pembayaran bunga pinjaman anggota.',
            ],
            [
                'source_module' => 'piutang-usaha',
                'transaction_type' => 'invoice-terbit',
                'debit_code' => '1102',
                'credit_code' => '4104',
                'notes' => 'Invoice jasa terbit: debit piutang usaha, kredit pendapatan jasa.',
            ],
            [
                'source_module' => 'piutang-usaha',
                'transaction_type' => 'pembayaran-piutang-kas',
                'debit_code' => '1001',
                'credit_code' => '1102',
                'notes' => 'Pembayaran piutang diterima tunai.',
            ],
            [
                'source_module' => 'piutang-usaha',
                'transaction_type' => 'pembayaran-piutang-bank',
                'debit_code' => '1002',
                'credit_code' => '1102',
                'notes' => 'Pembayaran piutang diterima melalui bank.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'retail-sale-indomaret-cash',
                'debit_code' => '1001',
                'credit_code' => '4102',
                'notes' => 'Penjualan retail Indomaret tunai.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'retail-sale-indomaret-salary-cut',
                'debit_code' => '1101',
                'credit_code' => '4102',
                'notes' => 'Penjualan retail Indomaret potong gaji.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'retail-sale-photocopy-cash',
                'debit_code' => '1001',
                'credit_code' => '4103',
                'notes' => 'Penjualan retail Photocopy tunai.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'retail-sale-photocopy-salary-cut',
                'debit_code' => '1101',
                'credit_code' => '4103',
                'notes' => 'Penjualan retail Photocopy potong gaji.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'barang-masuk-bayar-tunai',
                'debit_code' => '1201',
                'credit_code' => '1001',
                'notes' => 'Pembelian persediaan dibayar tunai.',
            ],
            [
                'source_module' => 'unit-usaha',
                'transaction_type' => 'barang-masuk-belum-bayar',
                'debit_code' => '1201',
                'credit_code' => '2001',
                'notes' => 'Pembelian persediaan secara utang.',
            ],
            [
                'source_module' => 'fixed-assets',
                'transaction_type' => 'perolehan-aset-tunai',
                'debit_code' => '1501',
                'credit_code' => '1001',
                'notes' => 'Perolehan aset tetap dibayar tunai.',
            ],
            [
                'source_module' => 'fixed-assets',
                'transaction_type' => 'penyusutan-kendaraan',
                'debit_code' => '5101',
                'credit_code' => '1591',
                'notes' => 'Pencatatan beban penyusutan kendaraan.',
            ],
            [
                'source_module' => 'manual',
                'transaction_type' => 'beban-operasional-tunai',
                'debit_code' => '5002',
                'credit_code' => '1001',
                'notes' => 'Beban operasional dibayar tunai.',
            ],
        ];

        foreach ($mappings as $mapping) {
            $debitAccount = $accounts->get($mapping['debit_code']);
            $creditAccount = $accounts->get($mapping['credit_code']);

            if (!$debitAccount || !$creditAccount) {
                continue;
            }

            GlAccountMapping::updateOrCreate(
                [
                    'source_module' => $mapping['source_module'],
                    'transaction_type' => $mapping['transaction_type'],
                ],
                [
                    'debit_account_id' => $debitAccount->id,
                    'credit_account_id' => $creditAccount->id,
                    'notes' => $mapping['notes'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDefaultSetup(): void
    {
        $groups = collect($this->defaultAccountGroups())->mapWithKeys(function (array $group) {
            $record = GlAccountGroup::updateOrCreate(
                ['code' => $group['code']],
                $group
            );

            return [$group['code'] => $record];
        });

        foreach ($this->defaultAccounts() as $account) {
            $group = $groups->get($account['group_code']);

            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'account_group_id' => $group?->id,
                    'normal_balance' => $account['normal_balance'],
                    'description' => $account['description'],
                    'is_header' => false,
                    'is_active' => true,
                ]
            );
        }

        $existingOpenPeriod = GlPeriod::query()->where('status', 'open')->first();

        GlPeriod::firstOrCreate(
            ['code' => now()->format('Ym')],
            [
                'name' => now()->translatedFormat('F Y'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => $existingOpenPeriod ? 'draft' : 'open',
                'notes' => 'Periode awal default GL.',
            ]
        );

        $this->seedDefaultMappings();
    }

    private function seedSampleJournals(): void
    {
        $accounts = ChartOfAccount::query()
            ->whereIn('code', ['1001', '1002', '1101', '1102', '1201', '1501', '1591', '2001', '3001', '4101', '4102', '4103', '4104', '5002', '5101'])
            ->get()
            ->keyBy('code');

        $period = GlPeriod::query()->where('status', 'open')->orderByDesc('start_date')->first();
        $costCenter = GlCostCenter::query()->first();

        $samples = [
            [
                'reference_number' => 'SAMPLE-GL-001',
                'entry_date' => now()->startOfMonth()->addDays(1)->toDateString(),
                'debit_code' => '1001',
                'credit_code' => '3001',
                'amount' => 25000000,
                'memo' => 'Setoran modal awal koperasi.',
                'status' => 'posted',
            ],
            [
                'reference_number' => 'SAMPLE-GL-002',
                'entry_date' => now()->startOfMonth()->addDays(3)->toDateString(),
                'debit_code' => '1201',
                'credit_code' => '2001',
                'amount' => 4500000,
                'memo' => 'Pembelian persediaan ATK secara utang.',
                'status' => 'posted',
            ],
            [
                'reference_number' => 'SAMPLE-GL-003',
                'entry_date' => now()->startOfMonth()->addDays(6)->toDateString(),
                'debit_code' => '1102',
                'credit_code' => '4104',
                'amount' => 12500000,
                'memo' => 'Invoice jasa driver bulanan ke pelanggan perusahaan.',
                'status' => 'posted',
            ],
            [
                'reference_number' => 'SAMPLE-GL-004',
                'entry_date' => now()->startOfMonth()->addDays(10)->toDateString(),
                'debit_code' => '1002',
                'credit_code' => '1102',
                'amount' => 5000000,
                'memo' => 'Pembayaran sebagian piutang usaha via transfer bank.',
                'status' => 'posted',
            ],
            [
                'reference_number' => 'SAMPLE-GL-005',
                'entry_date' => now()->startOfMonth()->addDays(12)->toDateString(),
                'debit_code' => '5002',
                'credit_code' => '1001',
                'amount' => 1750000,
                'memo' => 'Beban operasional harian dibayar tunai.',
                'status' => 'posted',
            ],
            [
                'reference_number' => 'SAMPLE-GL-006',
                'entry_date' => now()->startOfMonth()->addDays(15)->toDateString(),
                'debit_code' => '5101',
                'credit_code' => '1591',
                'amount' => 950000,
                'memo' => 'Penyusutan kendaraan bulan berjalan.',
                'status' => 'draft',
            ],
            [
                'reference_number' => 'SAMPLE-GL-007',
                'entry_date' => now()->startOfMonth()->addDays(18)->toDateString(),
                'debit_code' => '1501',
                'credit_code' => '1002',
                'amount' => 18000000,
                'memo' => 'Perolehan kendaraan operasional via bank.',
                'status' => 'draft',
            ],
        ];

        foreach ($samples as $sample) {
            $debitAccount = $accounts->get($sample['debit_code']);
            $creditAccount = $accounts->get($sample['credit_code']);

            if (!$debitAccount || !$creditAccount) {
                continue;
            }

            $journal = JournalEntry::query()->updateOrCreate(
                ['reference_number' => $sample['reference_number']],
                [
                    'entry_date' => $sample['entry_date'],
                    'period_id' => $period?->id,
                    'cost_center_id' => $costCenter?->id,
                    'reference_type' => 'sample',
                    'reference_id' => 0,
                    'source_module' => 'sample-data',
                    'debit_account_id' => $debitAccount->id,
                    'credit_account_id' => $creditAccount->id,
                    'amount' => $sample['amount'],
                    'memo' => $sample['memo'],
                    'status' => $sample['status'],
                    'posted_at' => $sample['status'] === 'posted' ? now() : null,
                    'created_by' => auth()->id(),
                    'posted_by' => $sample['status'] === 'posted' ? auth()->id() : null,
                ]
            );

            if ($journal->status === 'posted') {
                $this->journalService->postToGeneralLedger($journal);
            }
        }
    }

    private function annotateLegacyFlags(Collection $journals): void
    {
        $journals->each(function (JournalEntry $journal) {
            $journal->setAttribute('is_legacy', $this->isLegacyJournal($journal));
        });
    }

    private function flattenAccountsForDisplay(Collection $accounts): Collection
    {
        $childrenByParent = $accounts->groupBy(fn (ChartOfAccount $account) => $account->parent_id ?: 0);
        $result = collect();

        $walk = function ($parentId, int $level) use (&$walk, $childrenByParent, $result) {
            $children = $childrenByParent->get($parentId, collect())->sortBy('code')->values();

            foreach ($children as $child) {
                $child->setAttribute('display_level', $level);
                $result->push($child);
                $walk($child->id, $level + 1);
            }
        };

        $walk(0, 0);

        return $result;
    }

    private function descendantIds(ChartOfAccount $account): array
    {
        $allAccounts = ChartOfAccount::query()->select(['id', 'parent_id'])->get();
        $childrenByParent = $allAccounts->groupBy(fn (ChartOfAccount $item) => $item->parent_id ?: 0);
        $ids = [];

        $walk = function (int $parentId) use (&$walk, $childrenByParent, &$ids) {
            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                $ids[] = (int) $child->id;
                $walk((int) $child->id);
            }
        };

        $walk((int) $account->id);

        return $ids;
    }

    private function isLegacyJournal(JournalEntry $journal): bool
    {
        if (str_starts_with((string) $journal->reference_number, 'LEGACY-JRN-')) {
            return true;
        }

        return $journal->created_by === null
            || ($journal->status === 'posted' && $journal->posted_by === null)
            || $journal->period_id === null;
    }
}
