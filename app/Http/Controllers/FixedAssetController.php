<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\FixedAssetMutation;
use App\Models\GlCostCenter;
use App\Models\GlPeriod;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;

class FixedAssetController extends Controller
{
    public function __construct(
        private JournalService $journalService
    ) {}

    public function assets(): View
    {
        $assets = FixedAsset::query()
            ->with([
                'assetAccount',
                'accumulatedDepreciationAccount',
                'depreciationExpenseAccount',
                'acquisitionCreditAccount',
                'costCenter',
            ])
            ->withCount('depreciations')
            ->latest('in_service_date')
            ->latest('id')
            ->get();

        return view('fixed-assets.assets', [
            'title' => 'Fixed Aset - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Fixed Aset',
            'pageDescription' => 'Kelola register aset tetap, akun terkait, dan pencatatan perolehan aset koperasi.',
            'headerTabs' => $this->tabs('fixed-assets.assets'),
            'assets' => $assets,
            'assetCode' => $this->generateAssetCode(),
            'assetAccounts' => $this->accountOptions('asset'),
            'contraAssetAccounts' => $this->contraAssetAccounts(),
            'expenseAccounts' => $this->accountOptions('expense'),
            'creditAccounts' => $this->creditAccounts(),
            'costCenters' => GlCostCenter::query()->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function storeAsset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_code' => ['required', 'string', 'max:100', 'unique:fixed_assets,asset_code'],
            'asset_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'acquisition_date' => ['required', 'date'],
            'in_service_date' => ['required', 'date', 'after_or_equal:acquisition_date'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition_status' => ['required', 'in:baik,cukup,perlu-perbaikan'],
            'status' => ['required', 'in:active,idle,maintenance'],
            'acquisition_value' => ['required', 'numeric', 'min:0.01'],
            'residual_value' => ['required', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'asset_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'depreciation_expense_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'acquisition_credit_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'cost_center_id' => ['nullable', 'exists:gl_cost_centers,id'],
            'notes' => ['nullable', 'string'],
            'auto_post_acquisition' => ['nullable', 'boolean'],
        ]);

        if ((float) $validated['residual_value'] > (float) $validated['acquisition_value']) {
            return back()->withInput()->withErrors([
                'residual_value' => 'Nilai residu tidak boleh lebih besar dari nilai perolehan.',
            ]);
        }

        if (($validated['auto_post_acquisition'] ?? false) && empty($validated['acquisition_credit_account_id'])) {
            return back()->withInput()->withErrors([
                'acquisition_credit_account_id' => 'Pilih akun kredit asal dana jika ingin otomatis posting jurnal perolehan.',
            ]);
        }

        DB::transaction(function () use ($validated) {
            $asset = FixedAsset::create([
                'asset_code' => strtoupper(trim((string) $validated['asset_code'])),
                'asset_name' => $validated['asset_name'],
                'category' => $validated['category'],
                'acquisition_date' => $validated['acquisition_date'],
                'in_service_date' => $validated['in_service_date'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'location' => $validated['location'] ?? null,
                'condition_status' => $validated['condition_status'],
                'status' => $validated['status'],
                'acquisition_value' => $validated['acquisition_value'],
                'residual_value' => $validated['residual_value'],
                'useful_life_months' => $validated['useful_life_months'],
                'depreciation_method' => 'straight_line',
                'asset_account_id' => $validated['asset_account_id'],
                'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
                'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
                'acquisition_credit_account_id' => $validated['acquisition_credit_account_id'] ?? null,
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            if ($validated['auto_post_acquisition'] ?? false) {
                $this->createJournalForAsset(
                    asset: $asset,
                    entryDate: Carbon::parse($validated['acquisition_date']),
                    debitAccountId: (int) $validated['asset_account_id'],
                    creditAccountId: (int) $validated['acquisition_credit_account_id'],
                    amount: (float) $validated['acquisition_value'],
                    referenceNumber: $this->generateJournalReference('FAA'),
                    memo: 'Perolehan aset ' . $asset->asset_code . ' - ' . $asset->asset_name,
                );
            }
        });

        return redirect()->route('fixed-assets.assets')->with('success', 'Aset tetap berhasil ditambahkan.');
    }

    public function updateAsset(Request $request, FixedAsset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'asset_code' => ['required', 'string', 'max:100', Rule::unique('fixed_assets', 'asset_code')->ignore($asset->id)],
            'asset_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'acquisition_date' => ['required', 'date'],
            'in_service_date' => ['required', 'date', 'after_or_equal:acquisition_date'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'condition_status' => ['required', 'in:baik,cukup,perlu-perbaikan'],
            'status' => ['required', 'in:active,idle,maintenance,disposed'],
            'acquisition_value' => ['required', 'numeric', 'min:0.01'],
            'residual_value' => ['required', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'asset_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'accumulated_depreciation_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'depreciation_expense_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'acquisition_credit_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'cost_center_id' => ['nullable', 'exists:gl_cost_centers,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ((float) $validated['residual_value'] > (float) $validated['acquisition_value']) {
            return back()->withInput()->withErrors([
                'residual_value' => 'Nilai residu tidak boleh lebih besar dari nilai perolehan.',
            ]);
        }

        $asset->update([
            'asset_code' => strtoupper(trim((string) $validated['asset_code'])),
            'asset_name' => $validated['asset_name'],
            'category' => $validated['category'],
            'acquisition_date' => $validated['acquisition_date'],
            'in_service_date' => $validated['in_service_date'],
            'supplier_name' => $validated['supplier_name'] ?? null,
            'location' => $validated['location'] ?? null,
            'condition_status' => $validated['condition_status'],
            'status' => $validated['status'],
            'acquisition_value' => $validated['acquisition_value'],
            'residual_value' => $validated['residual_value'],
            'useful_life_months' => $validated['useful_life_months'],
            'asset_account_id' => $validated['asset_account_id'],
            'accumulated_depreciation_account_id' => $validated['accumulated_depreciation_account_id'],
            'depreciation_expense_account_id' => $validated['depreciation_expense_account_id'],
            'acquisition_credit_account_id' => $validated['acquisition_credit_account_id'] ?? null,
            'cost_center_id' => $validated['cost_center_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'disposed_at' => $validated['status'] === 'disposed' ? ($asset->disposed_at ?? now()->toDateString()) : null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('fixed-assets.assets')->with('success', 'Data fixed aset berhasil diperbarui.');
    }

    public function depreciation(Request $request): View
    {
        $selectedYear = max(2020, (int) $request->input('year', now()->year));
        $selectedMonth = min(12, max(1, (int) $request->input('month', now()->month)));
        $periodEnd = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();

        $assets = FixedAsset::query()
            ->with([
                'depreciations' => fn ($query) => $query->orderBy('period_year')->orderBy('period_month'),
                'depreciationExpenseAccount',
                'accumulatedDepreciationAccount',
                'costCenter',
            ])
            ->orderBy('asset_name')
            ->get();

        $existingDepreciations = FixedAssetDepreciation::query()
            ->with(['asset', 'journalEntry'])
            ->where('period_year', $selectedYear)
            ->where('period_month', $selectedMonth)
            ->orderByDesc('id')
            ->get();

        return view('fixed-assets.depreciation', [
            'title' => 'Depresiasi - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Depresiasi',
            'pageDescription' => 'Hitung penyusutan bulanan, review nilai buku, dan posting jurnal beban penyusutan.',
            'headerTabs' => $this->tabs('fixed-assets.depreciation'),
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'periodEnd' => $periodEnd,
            'previewRows' => $this->buildDepreciationPreview($assets, $selectedYear, $selectedMonth),
            'existingDepreciations' => $existingDepreciations,
            'openPeriods' => GlPeriod::query()->whereIn('status', ['draft', 'open'])->orderByDesc('start_date')->get(),
        ]);
    }

    public function runDepreciation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'post_to_journal' => ['nullable', 'boolean'],
        ]);

        $assets = FixedAsset::query()
            ->with('depreciations')
            ->orderBy('asset_name')
            ->get();
        $rows = $this->buildDepreciationPreview($assets, (int) $validated['year'], (int) $validated['month']);
        $rowsToProcess = $rows->filter(fn (array $row) => $row['eligible']);

        if ($rowsToProcess->isEmpty()) {
            return back()->withErrors([
                'depreciation' => 'Tidak ada aset yang memenuhi syarat penyusutan untuk periode tersebut.',
            ]);
        }

        DB::transaction(function () use ($rowsToProcess, $validated) {
            foreach ($rowsToProcess as $row) {
                $asset = FixedAsset::query()->findOrFail($row['asset_id']);

                $depreciation = FixedAssetDepreciation::create([
                    'fixed_asset_id' => $asset->id,
                    'period_year' => (int) $validated['year'],
                    'period_month' => (int) $validated['month'],
                    'depreciation_date' => $row['depreciation_date'],
                    'amount' => $row['amount'],
                    'accumulated_amount' => $row['accumulated_amount'],
                    'book_value' => $row['book_value'],
                    'status' => ($validated['post_to_journal'] ?? false) ? 'posted' : 'draft',
                    'notes' => 'Penyusutan otomatis periode ' . sprintf('%02d/%04d', $validated['month'], $validated['year']),
                ]);

                if ($validated['post_to_journal'] ?? false) {
                    $journal = $this->createJournalForAsset(
                        asset: $asset,
                        entryDate: Carbon::parse($row['depreciation_date']),
                        debitAccountId: (int) $asset->depreciation_expense_account_id,
                        creditAccountId: (int) $asset->accumulated_depreciation_account_id,
                        amount: (float) $row['amount'],
                        referenceNumber: $this->generateJournalReference('FAD'),
                        memo: 'Penyusutan aset ' . $asset->asset_code . ' periode ' . sprintf('%02d/%04d', $validated['month'], $validated['year']),
                    );

                    $depreciation->update([
                        'status' => 'posted',
                        'journal_entry_id' => $journal->id,
                    ]);
                }

                $asset->update([
                    'last_depreciation_at' => $row['depreciation_date'],
                ]);
            }
        });

        return redirect()->route('fixed-assets.depreciation', [
            'year' => $validated['year'],
            'month' => $validated['month'],
        ])->with('success', 'Proses depresiasi berhasil dijalankan untuk periode terpilih.');
    }

    public function postDepreciation(FixedAssetDepreciation $depreciation): RedirectResponse
    {
        if ($depreciation->status === 'posted' && $depreciation->journal_entry_id) {
            return back()->with('success', 'Depresiasi tersebut sudah diposting sebelumnya.');
        }

        $asset = $depreciation->asset()->firstOrFail();

        DB::transaction(function () use ($depreciation, $asset) {
            $journal = $this->createJournalForAsset(
                asset: $asset,
                entryDate: Carbon::parse($depreciation->depreciation_date),
                debitAccountId: (int) $asset->depreciation_expense_account_id,
                creditAccountId: (int) $asset->accumulated_depreciation_account_id,
                amount: (float) $depreciation->amount,
                referenceNumber: $this->generateJournalReference('FAD'),
                memo: 'Posting depresiasi aset ' . $asset->asset_code . ' periode ' . sprintf('%02d/%04d', $depreciation->period_month, $depreciation->period_year),
            );

            $depreciation->update([
                'status' => 'posted',
                'journal_entry_id' => $journal->id,
            ]);
        });

        return back()->with('success', 'Draft depresiasi berhasil diposting ke jurnal.');
    }

    public function mutations(): View
    {
        $assets = FixedAsset::query()
            ->with(['mutations', 'depreciations'])
            ->latest('updated_at')
            ->get();

        $mutations = FixedAssetMutation::query()
            ->with(['asset', 'disposalDebitAccount', 'disposalGainAccount', 'disposalLossAccount'])
            ->latest('mutation_date')
            ->latest('id')
            ->get();

        return view('fixed-assets.mutations', [
            'title' => 'Mutasi Aset - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Mutasi Aset',
            'pageDescription' => 'Kelola perpindahan lokasi, perubahan status, maintenance, dan penghentian aset tetap.',
            'headerTabs' => $this->tabs('fixed-assets.mutations'),
            'assets' => $assets,
            'mutations' => $mutations,
            'disposalDebitAccounts' => $this->accountOptions('asset')->whereIn('code', ['1001', '1002'])->values(),
            'incomeAccounts' => $this->accountOptions('income'),
            'expenseAccounts' => $this->accountOptions('expense'),
        ]);
    }

    public function reports(Request $request): View
    {
        $reportData = $this->reportData($request);

        return view('fixed-assets.reports', [
            'title' => 'Laporan Fixed Aset - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Laporan Fixed Aset',
            'pageDescription' => 'Rekap register aset, penyusutan per periode, aset mendekati akhir umur manfaat, dan histori disposal.',
            'headerTabs' => $this->tabs('fixed-assets.reports'),
            ...$reportData,
        ]);
    }

    public function exportReportsExcel(Request $request)
    {
        $reportData = $this->reportData($request);

        $directory = storage_path('app/exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = 'laporan-fixed-aset-' . $reportData['selectedYear'] . sprintf('%02d', $reportData['selectedMonth']) . '-' . now()->format('His') . '.xlsx';
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        $writer = new XlsxWriter();
        $writer->openToFile($filePath);

        $writer->addRow(Row::fromValues(['Laporan Fixed Aset']));
        $writer->addRow(Row::fromValues(['Periode', $reportData['periodEnd']->translatedFormat('F Y')]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Ringkasan']));
        $writer->addRow(Row::fromValues(['Total Register', $reportData['assets']->count()]));
        $writer->addRow(Row::fromValues(['Nilai Perolehan', (float) $reportData['assets']->sum(fn ($asset) => (float) $asset->acquisition_value)]));
        $writer->addRow(Row::fromValues(['Nilai Buku', (float) $reportData['assets']->sum(fn ($asset) => (float) $asset->book_value)]));
        $writer->addRow(Row::fromValues(['Depresiasi Periode', (float) $reportData['periodDepreciations']->sum(fn ($item) => (float) $item->amount)]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Ringkasan Kategori']));
        $writer->addRow(Row::fromValues(['Kategori', 'Jumlah Aset', 'Nilai Perolehan', 'Nilai Buku']));
        foreach ($reportData['categorySummary'] as $row) {
            $writer->addRow(Row::fromValues([
                $row['category'],
                $row['count'],
                (float) $row['acquisition_value'],
                (float) $row['book_value'],
            ]));
        }
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Aset Mendekati Akhir Umur Manfaat']));
        $writer->addRow(Row::fromValues(['Kode', 'Nama Aset', 'Kategori', 'Sisa Bulan', 'Nilai Buku']));
        foreach ($reportData['nearEndOfLife'] as $asset) {
            $writer->addRow(Row::fromValues([
                $asset->asset_code,
                $asset->asset_name,
                $asset->category,
                (int) $asset->remaining_months,
                (float) $asset->book_value,
            ]));
        }
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Depresiasi Periode']));
        $writer->addRow(Row::fromValues(['Kode', 'Nama Aset', 'Tanggal', 'Jumlah', 'Akumulasi', 'Nilai Buku', 'Status']));
        foreach ($reportData['periodDepreciations'] as $item) {
            $writer->addRow(Row::fromValues([
                $item->asset?->asset_code ?? '',
                $item->asset?->asset_name ?? '',
                optional($item->depreciation_date)->format('Y-m-d') ?? '',
                (float) $item->amount,
                (float) $item->accumulated_amount,
                (float) $item->book_value,
                strtoupper((string) $item->status),
            ]));
        }
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues(['Disposal Periode']));
        $writer->addRow(Row::fromValues(['Kode', 'Nama Aset', 'Tanggal', 'Nilai Disposal', 'Referensi Jurnal', 'Catatan']));
        foreach ($reportData['disposals'] as $item) {
            $writer->addRow(Row::fromValues([
                $item->asset?->asset_code ?? '',
                $item->asset?->asset_name ?? '',
                optional($item->mutation_date)->format('Y-m-d') ?? '',
                (float) ($item->disposal_value ?? 0),
                $item->disposal_reference_number ?? '',
                $item->notes ?? '',
            ]));
        }

        $writer->close();

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function storeMutation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fixed_asset_id' => ['required', 'exists:fixed_assets,id'],
            'mutation_date' => ['required', 'date'],
            'mutation_type' => ['required', 'in:location,status,maintenance,disposal'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'to_status' => ['nullable', 'in:active,idle,maintenance,disposed'],
            'disposal_value' => ['nullable', 'numeric', 'min:0'],
            'disposal_debit_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'disposal_gain_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'disposal_loss_account_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'auto_post_disposal' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['mutation_type'] === 'disposal' && ($validated['auto_post_disposal'] ?? false)) {
            foreach (['disposal_debit_account_id', 'disposal_gain_account_id', 'disposal_loss_account_id'] as $field) {
                if (empty($validated[$field])) {
                    return back()->withInput()->withErrors([
                        $field => 'Lengkapi akun disposal agar jurnal penghentian aset bisa dibuat otomatis.',
                    ]);
                }
            }
        }

        try {
            DB::transaction(function () use ($validated) {
                $asset = FixedAsset::query()->findOrFail($validated['fixed_asset_id']);

                if (($validated['mutation_type'] === 'disposal') && ($validated['auto_post_disposal'] ?? false)) {
                    if (!$asset->asset_account_id || !$asset->accumulated_depreciation_account_id) {
                        throw new RuntimeException('Aset ini belum memiliki akun aset atau akun akumulasi penyusutan yang lengkap untuk disposal otomatis.');
                    }
                }

                $newLocation = $validated['mutation_type'] === 'location'
                    ? ($validated['to_location'] ?? $asset->location)
                    : $asset->location;
                $newStatus = in_array($validated['mutation_type'], ['status', 'maintenance', 'disposal'], true)
                    ? ($validated['to_status'] ?? ($validated['mutation_type'] === 'maintenance' ? 'maintenance' : ($validated['mutation_type'] === 'disposal' ? 'disposed' : $asset->status)))
                    : $asset->status;

                $mutation = FixedAssetMutation::create([
                    'fixed_asset_id' => $asset->id,
                    'mutation_date' => $validated['mutation_date'],
                    'mutation_type' => $validated['mutation_type'],
                    'from_location' => $asset->location,
                    'to_location' => $newLocation,
                    'from_status' => $asset->status,
                    'to_status' => $newStatus,
                    'disposal_value' => $validated['disposal_value'] ?? null,
                    'disposal_debit_account_id' => $validated['disposal_debit_account_id'] ?? null,
                    'disposal_gain_account_id' => $validated['disposal_gain_account_id'] ?? null,
                    'disposal_loss_account_id' => $validated['disposal_loss_account_id'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $asset->update([
                    'location' => $newLocation,
                    'status' => $newStatus,
                    'disposed_at' => $newStatus === 'disposed' ? $validated['mutation_date'] : null,
                    'disposal_value' => $newStatus === 'disposed' ? ($validated['disposal_value'] ?? 0) : null,
                    'is_active' => $newStatus !== 'disposed',
                ]);

                if ($validated['mutation_type'] === 'disposal' && ($validated['auto_post_disposal'] ?? false)) {
                    $referenceNumber = $this->generateJournalReference('FADP');
                    $this->postDisposalJournals(
                        asset: $asset->fresh(['depreciations']),
                        mutation: $mutation,
                        referenceNumber: $referenceNumber,
                        disposalDate: Carbon::parse($validated['mutation_date']),
                        disposalValue: (float) ($validated['disposal_value'] ?? 0),
                        disposalDebitAccountId: (int) $validated['disposal_debit_account_id'],
                        disposalGainAccountId: (int) $validated['disposal_gain_account_id'],
                        disposalLossAccountId: (int) $validated['disposal_loss_account_id'],
                    );

                    $mutation->update([
                        'disposal_reference_number' => $referenceNumber,
                    ]);
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors([
                'mutation' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('fixed-assets.mutations')->with('success', 'Mutasi aset berhasil disimpan.');
    }

    private function tabs(string $activeRoute): array
    {
        return [
            [
                'route' => 'fixed-assets.assets',
                'label' => 'Fixed Aset',
                'active' => $activeRoute === 'fixed-assets.assets',
            ],
            [
                'route' => 'fixed-assets.depreciation',
                'label' => 'Depresiasi',
                'active' => $activeRoute === 'fixed-assets.depreciation',
            ],
            [
                'route' => 'fixed-assets.mutations',
                'label' => 'Mutasi Aset',
                'active' => $activeRoute === 'fixed-assets.mutations',
            ],
            [
                'route' => 'fixed-assets.reports',
                'label' => 'Laporan FA',
                'active' => $activeRoute === 'fixed-assets.reports',
            ],
        ];
    }

    private function buildDepreciationPreview(Collection $assets, int $year, int $month): Collection
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return $assets->map(function (FixedAsset $asset) use ($year, $month, $periodEnd) {
            $existingCurrent = $asset->depreciations
                ->first(fn (FixedAssetDepreciation $item) => (int) $item->period_year === $year && (int) $item->period_month === $month);
            $completedCount = $asset->depreciations
                ->filter(fn (FixedAssetDepreciation $item) => ((int) $item->period_year < $year)
                    || ((int) $item->period_year === $year && (int) $item->period_month < $month))
                ->count();
            $accumulatedBefore = (float) $asset->depreciations
                ->filter(fn (FixedAssetDepreciation $item) => ((int) $item->period_year < $year)
                    || ((int) $item->period_year === $year && (int) $item->period_month < $month))
                ->sum('amount');
            $remaining = max(0, $asset->depreciable_base - $accumulatedBefore);
            $monthlyAmount = min($asset->monthly_depreciation, $remaining);

            $eligible = $asset->is_active
                && $asset->status !== 'disposed'
                && Carbon::parse($asset->in_service_date)->lte($periodEnd)
                && ($asset->disposed_at === null || Carbon::parse($asset->disposed_at)->gt($periodEnd))
                && $asset->depreciation_method === 'straight_line'
                && !$existingCurrent
                && $completedCount < (int) $asset->useful_life_months
                && $monthlyAmount > 0
                && $asset->depreciation_expense_account_id
                && $asset->accumulated_depreciation_account_id;

            $amount = $eligible ? round($monthlyAmount, 2) : 0.0;
            $accumulatedAfter = round($accumulatedBefore + $amount, 2);
            $bookValue = round(max((float) $asset->residual_value, (float) $asset->acquisition_value - $accumulatedAfter), 2);

            return [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category,
                'eligible' => $eligible,
                'status_label' => $existingCurrent ? 'Sudah diproses' : ($eligible ? 'Siap diproses' : 'Tidak memenuhi syarat'),
                'amount' => $amount,
                'accumulated_before' => round($accumulatedBefore, 2),
                'accumulated_amount' => $accumulatedAfter,
                'book_value' => $bookValue,
                'depreciation_date' => $periodEnd->toDateString(),
                'existing_id' => $existingCurrent?->id,
            ];
        });
    }

    private function reportData(Request $request): array
    {
        $selectedYear = max(2020, (int) $request->input('year', now()->year));
        $selectedMonth = min(12, max(1, (int) $request->input('month', now()->month)));
        $periodStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $assets = FixedAsset::query()
            ->with(['depreciations', 'mutations', 'costCenter'])
            ->orderBy('asset_name')
            ->get();

        $periodDepreciations = FixedAssetDepreciation::query()
            ->with(['asset', 'journalEntry'])
            ->where('period_year', $selectedYear)
            ->where('period_month', $selectedMonth)
            ->orderByDesc('amount')
            ->get();

        $disposals = FixedAssetMutation::query()
            ->with('asset')
            ->where('mutation_type', 'disposal')
            ->whereBetween('mutation_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->latest('mutation_date')
            ->get();

        $nearEndOfLife = $assets
            ->map(function (FixedAsset $asset) {
                $depreciatedMonths = $asset->depreciations->count();
                $remainingMonths = max(0, (int) $asset->useful_life_months - $depreciatedMonths);
                $asset->setAttribute('remaining_months', $remainingMonths);

                return $asset;
            })
            ->filter(fn (FixedAsset $asset) => $asset->status !== 'disposed' && (int) $asset->remaining_months <= 12)
            ->sortBy('remaining_months')
            ->values();

        $categorySummary = $assets
            ->groupBy('category')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'count' => $group->count(),
                'acquisition_value' => $group->sum(fn (FixedAsset $asset) => (float) $asset->acquisition_value),
                'book_value' => $group->sum(fn (FixedAsset $asset) => (float) $asset->book_value),
            ])
            ->sortByDesc('acquisition_value')
            ->values();

        return [
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'periodEnd' => $periodEnd,
            'assets' => $assets,
            'periodDepreciations' => $periodDepreciations,
            'disposals' => $disposals,
            'nearEndOfLife' => $nearEndOfLife,
            'categorySummary' => $categorySummary,
        ];
    }

    private function createJournalForAsset(
        FixedAsset $asset,
        Carbon $entryDate,
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $referenceNumber,
        string $memo
    ): JournalEntry {
        $period = GlPeriod::query()
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $entryDate->toDateString())
            ->whereDate('end_date', '>=', $entryDate->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if (!$period) {
            throw new RuntimeException('Periode akuntansi open untuk tanggal transaksi aset tidak ditemukan.');
        }

        $journal = JournalEntry::create([
            'entry_date' => $entryDate->toDateString(),
            'period_id' => $period->id,
            'cost_center_id' => $asset->cost_center_id,
            'reference_type' => 'fixed_asset',
            'reference_id' => $asset->id,
            'source_module' => 'fixed-assets',
            'reference_number' => $referenceNumber,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'amount' => $amount,
            'memo' => $memo,
            'status' => 'posted',
            'posted_at' => now(),
            'created_by' => Auth::id(),
            'posted_by' => Auth::id(),
        ]);

        $this->journalService->postToGeneralLedger($journal);

        return $journal;
    }

    private function postDisposalJournals(
        FixedAsset $asset,
        FixedAssetMutation $mutation,
        string $referenceNumber,
        Carbon $disposalDate,
        float $disposalValue,
        int $disposalDebitAccountId,
        int $disposalGainAccountId,
        int $disposalLossAccountId
    ): void {
        $accumulatedDepreciation = round((float) $asset->depreciations()->sum('amount'), 2);
        $bookValue = round(max(0, (float) $asset->acquisition_value - $accumulatedDepreciation), 2);
        $proceeds = round(max(0, $disposalValue), 2);

        if ($accumulatedDepreciation > 0) {
            $this->createJournalForAsset(
                asset: $asset,
                entryDate: $disposalDate,
                debitAccountId: (int) $asset->accumulated_depreciation_account_id,
                creditAccountId: (int) $asset->asset_account_id,
                amount: $accumulatedDepreciation,
                referenceNumber: $referenceNumber . '-01',
                memo: 'Reklas akumulasi penyusutan disposal aset ' . $asset->asset_code,
            );
        }

        if ($proceeds > 0 && $proceeds <= $bookValue) {
            $this->createJournalForAsset(
                asset: $asset,
                entryDate: $disposalDate,
                debitAccountId: $disposalDebitAccountId,
                creditAccountId: (int) $asset->asset_account_id,
                amount: $proceeds,
                referenceNumber: $referenceNumber . '-02',
                memo: 'Penerimaan disposal aset ' . $asset->asset_code,
            );
        }

        if ($proceeds > $bookValue && $bookValue > 0) {
            $this->createJournalForAsset(
                asset: $asset,
                entryDate: $disposalDate,
                debitAccountId: $disposalDebitAccountId,
                creditAccountId: (int) $asset->asset_account_id,
                amount: $bookValue,
                referenceNumber: $referenceNumber . '-02',
                memo: 'Penghapusan nilai buku disposal aset ' . $asset->asset_code,
            );
        }

        $lossAmount = round(max(0, $bookValue - $proceeds), 2);
        if ($lossAmount > 0) {
            $this->createJournalForAsset(
                asset: $asset,
                entryDate: $disposalDate,
                debitAccountId: $disposalLossAccountId,
                creditAccountId: (int) $asset->asset_account_id,
                amount: $lossAmount,
                referenceNumber: $referenceNumber . '-03',
                memo: 'Rugi disposal aset ' . $asset->asset_code,
            );
        }

        $gainAmount = round(max(0, $proceeds - $bookValue), 2);
        if ($gainAmount > 0) {
            $this->createJournalForAsset(
                asset: $asset,
                entryDate: $disposalDate,
                debitAccountId: $disposalDebitAccountId,
                creditAccountId: $disposalGainAccountId,
                amount: $gainAmount,
                referenceNumber: $referenceNumber . '-03',
                memo: 'Laba disposal aset ' . $asset->asset_code,
            );
        }
    }

    private function accountOptions(string $type): Collection
    {
        return ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->where('account_type', $type)
            ->orderBy('code')
            ->get();
    }

    private function contraAssetAccounts(): Collection
    {
        return ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->where('account_type', 'asset')
            ->where('normal_balance', 'credit')
            ->orderBy('code')
            ->get();
    }

    private function creditAccounts(): Collection
    {
        return ChartOfAccount::query()
            ->where('is_active', true)
            ->where('is_header', false)
            ->whereIn('account_type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();
    }

    private function generateAssetCode(): string
    {
        $datePrefix = now()->format('Ym');
        $count = FixedAsset::query()->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count() + 1;

        return 'FA-' . $datePrefix . '-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalReference(string $prefix): string
    {
        $count = JournalEntry::query()->whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
