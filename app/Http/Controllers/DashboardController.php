<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\FixedAssetMutation;
use App\Models\GeneralLedger;
use App\Models\GlPeriod;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\Official;
use App\Models\PiutangUsahaCompany;
use App\Models\PiutangUsahaContract;
use App\Models\PiutangUsahaInvoice;
use App\Models\PiutangUsahaPayment;
use App\Models\Position;
use App\Models\Saving;
use App\Models\SavingType;
use App\Models\UnitUsahaInventory;
use App\Models\UnitUsahaPurchase;
use App\Models\UnitUsahaSale;
use App\Models\UnitUsahaService;
use App\Models\UnitUsahaStockOpname;
use App\Models\User;
use App\Services\RetailInventoryBridgeService;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __construct(private RetailInventoryBridgeService $retailInventoryBridgeService) {}

    public function index()
    {
        $user = auth()->user();
        $today = today();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $canViewUnitUsaha = (bool) $user?->canAccessUnitUsaha();
        $canViewAdminModules = (bool) $user?->isAdministrator();

        if ($canViewUnitUsaha) {
            $this->retailInventoryBridgeService->syncAllRetailItemsToInventories();
        }

        $totalSavings = (float) Saving::sum('amount');
        $outstandingLoans = (float) Loan::whereIn('status', ['approved', 'disbursed', 'active'])->sum('remaining_balance');
        $todayInstallments = (float) LoanPayment::whereDate('payment_date', $today)->sum('total_paid');
        $pendingLoans = Loan::where('status', 'pending')->count();
        $approvedLoans = Loan::where('status', 'approved')->count();

        $unitUsahaMonthSales = $canViewUnitUsaha
            ? (float) UnitUsahaSale::whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('total_amount')
            : 0.0;
        $unitUsahaTodaySales = $canViewUnitUsaha
            ? (float) UnitUsahaSale::whereDate('sale_date', $today)->sum('total_amount')
            : 0.0;
        $lowStockCount = $canViewUnitUsaha
            ? UnitUsahaInventory::whereColumn('stock', '<=', 'minimum_stock')->where('is_active', true)->count()
            : 0;
        $activeInventoryCount = $canViewUnitUsaha
            ? UnitUsahaInventory::where('is_active', true)->count()
            : 0;

        $openInvoicesQuery = PiutangUsahaInvoice::query()->where('outstanding_amount', '>', 0);
        $totalReceivables = $canViewAdminModules ? (float) (clone $openInvoicesQuery)->sum('outstanding_amount') : 0.0;
        $overdueInvoices = $canViewAdminModules
            ? PiutangUsahaInvoice::query()
                ->where('outstanding_amount', '>', 0)
                ->whereDate('due_date', '<', $today)
                ->count()
            : 0;
        $receivedThisMonth = $canViewAdminModules
            ? (float) PiutangUsahaPayment::whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->sum('amount')
            : 0.0;

        $openPeriod = $canViewAdminModules
            ? GlPeriod::query()->where('status', 'open')->latest('start_date')->first()
            : null;
        $draftJournals = $canViewAdminModules ? JournalEntry::where('status', 'draft')->count() : 0;
        $postedJournalsThisMonth = $canViewAdminModules
            ? JournalEntry::where('status', 'posted')->whereBetween('entry_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->count()
            : 0;
        $trialBalanceGap = $canViewAdminModules
            ? abs((float) GeneralLedger::sum('debit') - (float) GeneralLedger::sum('credit'))
            : 0.0;

        $fixedAssets = $canViewUnitUsaha
            ? FixedAsset::query()
                ->withCount('depreciations')
                ->withSum('depreciations as accumulated_depreciation_sum', 'amount')
                ->get()
            : collect();
        $activeAssets = $fixedAssets->where('is_active', true);
        $bookValueAssets = $activeAssets->sum(function (FixedAsset $asset) {
            $accumulated = (float) ($asset->accumulated_depreciation_sum ?? 0);

            return max((float) $asset->residual_value, (float) $asset->acquisition_value - $accumulated);
        });
        $assetsNearEndOfLife = $activeAssets->filter(function (FixedAsset $asset) {
            $remainingMonths = (int) $asset->useful_life_months - (int) $asset->depreciations_count;

            return $remainingMonths >= 0 && $remainingMonths <= 3;
        })->count();
        $depreciationThisMonth = $canViewUnitUsaha
            ? (float) FixedAssetDepreciation::where('period_year', (int) $monthStart->year)
                ->where('period_month', (int) $monthStart->month)
                ->sum('amount')
            : 0.0;
        $disposalsThisMonth = $canViewUnitUsaha
            ? FixedAssetMutation::where('mutation_type', 'disposal')
                ->whereBetween('mutation_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->count()
            : 0;

        $highlightCards = collect([
            [
                'label' => 'Anggota Aktif',
                'value' => number_format(Member::where('status', 'active')->count()),
                'caption' => 'Anggota yang bisa bertransaksi saat ini.',
                'icon' => 'fas fa-users',
                'accent' => 'sky',
            ],
            [
                'label' => 'Total Simpanan',
                'value' => $this->formatCurrency($totalSavings),
                'caption' => 'Akumulasi seluruh simpanan anggota.',
                'icon' => 'fas fa-piggy-bank',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Outstanding Pinjaman',
                'value' => $this->formatCurrency($outstandingLoans),
                'caption' => 'Saldo pinjaman aktif dan siap dipantau.',
                'icon' => 'fas fa-hand-holding-dollar',
                'accent' => 'amber',
            ],
            $canViewUnitUsaha ? [
                'label' => 'Omzet Unit Usaha Bulan Ini',
                'value' => $this->formatCurrency($unitUsahaMonthSales),
                'caption' => 'Penjualan dari POS web dan retail yang sudah terintegrasi.',
                'icon' => 'fas fa-store',
                'accent' => 'rose',
            ] : null,
            $canViewAdminModules ? [
                'label' => 'Piutang Usaha Berjalan',
                'value' => $this->formatCurrency($totalReceivables),
                'caption' => 'Sisa invoice perusahaan yang belum lunas.',
                'icon' => 'fas fa-file-invoice-dollar',
                'accent' => 'indigo',
            ] : null,
            $canViewUnitUsaha ? [
                'label' => 'Nilai Buku Aset',
                'value' => $this->formatCurrency($bookValueAssets),
                'caption' => 'Estimasi nilai buku aset aktif koperasi.',
                'icon' => 'fas fa-building',
                'accent' => 'teal',
            ] : null,
        ])->filter()->values();

        return view('dashboard', [
            'title' => 'Dashboard - Koperasi Digital Mandiri',
            'sectionLabel' => 'Ringkasan Aplikasi',
            'pageTitle' => 'Dashboard Utama Koperasi',
            'pageDescription' => 'Satu halaman untuk melihat kondisi seluruh modul koperasi, tindak lanjut yang dibutuhkan, dan akses cepat ke menu kerja utama.',
            'savingTypes' => SavingType::orderBy('name')->get(),
            'eligibleMembers' => Member::where('status', 'active')->orderBy('name')->get(),
            'activeLoansForPayment' => Loan::with('member')
                ->whereIn('status', ['disbursed', 'active'])
                ->latest()
                ->take(20)
                ->get(),
            'highlightCards' => $highlightCards,
            'activityChart' => $this->getActivityChart($canViewUnitUsaha, $canViewAdminModules),
            'modulePanels' => $this->buildModulePanels(
                canViewUnitUsaha: $canViewUnitUsaha,
                canViewAdminModules: $canViewAdminModules,
                totalSavings: $totalSavings,
                outstandingLoans: $outstandingLoans,
                todayInstallments: $todayInstallments,
                pendingLoans: $pendingLoans,
                approvedLoans: $approvedLoans,
                unitUsahaTodaySales: $unitUsahaTodaySales,
                unitUsahaMonthSales: $unitUsahaMonthSales,
                lowStockCount: $lowStockCount,
                activeInventoryCount: $activeInventoryCount,
                totalReceivables: $totalReceivables,
                overdueInvoices: $overdueInvoices,
                receivedThisMonth: $receivedThisMonth,
                openPeriod: $openPeriod?->name,
                draftJournals: $draftJournals,
                postedJournalsThisMonth: $postedJournalsThisMonth,
                trialBalanceGap: $trialBalanceGap,
                bookValueAssets: $bookValueAssets,
                assetsNearEndOfLife: $assetsNearEndOfLife,
                depreciationThisMonth: $depreciationThisMonth,
                disposalsThisMonth: $disposalsThisMonth,
            ),
            'attentionItems' => $this->getAttentionItems(
                canViewUnitUsaha: $canViewUnitUsaha,
                canViewAdminModules: $canViewAdminModules,
                lowStockCount: $lowStockCount,
                pendingLoans: $pendingLoans,
                approvedLoans: $approvedLoans,
                overdueInvoices: $overdueInvoices,
                draftJournals: $draftJournals,
                assetsNearEndOfLife: $assetsNearEndOfLife,
            ),
            'recentActivities' => $this->getRecentActivities($canViewUnitUsaha, $canViewAdminModules),
            'masterStats' => [
                'members' => Member::count(),
                'inactive_members' => Member::where('status', 'inactive')->count(),
                'officials' => Official::count(),
                'active_officials' => Official::where('is_active', true)->count(),
                'users' => $canViewAdminModules ? User::count() : null,
                'positions' => $canViewAdminModules ? Position::count() : null,
                'active_accounts' => $canViewAdminModules ? ChartOfAccount::where('is_active', true)->count() : null,
                'company_count' => $canViewAdminModules ? PiutangUsahaCompany::where('is_active', true)->count() : null,
                'contract_count' => $canViewAdminModules ? PiutangUsahaContract::where('status', 'active')->count() : null,
                'inventory_count' => $canViewUnitUsaha ? UnitUsahaInventory::where('is_active', true)->count() : null,
                'service_count' => $canViewUnitUsaha ? UnitUsahaService::where('is_active', true)->count() : null,
            ],
        ]);
    }

    private function buildModulePanels(
        bool $canViewUnitUsaha,
        bool $canViewAdminModules,
        float $totalSavings,
        float $outstandingLoans,
        float $todayInstallments,
        int $pendingLoans,
        int $approvedLoans,
        float $unitUsahaTodaySales,
        float $unitUsahaMonthSales,
        int $lowStockCount,
        int $activeInventoryCount,
        float $totalReceivables,
        int $overdueInvoices,
        float $receivedThisMonth,
        ?string $openPeriod,
        int $draftJournals,
        int $postedJournalsThisMonth,
        float $trialBalanceGap,
        float $bookValueAssets,
        int $assetsNearEndOfLife,
        float $depreciationThisMonth,
        int $disposalsThisMonth,
    ): Collection {
        return collect([
            [
                'title' => 'Simpan Pinjam',
                'icon' => 'fas fa-wallet',
                'accent' => 'emerald',
                'description' => 'Pantau simpanan, pinjaman, approval, pencairan, dan angsuran dalam satu blok kerja.',
                'stats' => [
                    ['label' => 'Total simpanan', 'value' => $this->formatCurrency($totalSavings)],
                    ['label' => 'Outstanding pinjaman', 'value' => $this->formatCurrency($outstandingLoans)],
                    ['label' => 'Angsuran hari ini', 'value' => $this->formatCurrency($todayInstallments)],
                    ['label' => 'Menunggu approval', 'value' => number_format($pendingLoans)],
                    ['label' => 'Siap dicairkan', 'value' => number_format($approvedLoans)],
                ],
                'shortcuts' => [
                    ['label' => 'Dashboard', 'route' => 'simpan-pinjam'],
                    ['label' => 'Pengajuan', 'route' => 'simpan-pinjam.loans.applications'],
                    ['label' => 'Approval', 'route' => 'simpan-pinjam.loans.approvals'],
                    ['label' => 'Pencairan', 'route' => 'simpan-pinjam.loans.disbursement'],
                    ['label' => 'Monitoring', 'route' => 'simpan-pinjam.loans.monitoring'],
                    ['label' => 'Angsuran', 'route' => 'simpan-pinjam.installments'],
                    ['label' => 'Simpanan', 'route' => 'simpan-pinjam.savings'],
                ],
                'visible' => true,
            ],
            [
                'title' => 'Unit Usaha',
                'icon' => 'fas fa-store',
                'accent' => 'rose',
                'description' => 'Ringkasan penjualan, stok, pembelian, stock opname, dan laporan operasional unit usaha.',
                'stats' => [
                    ['label' => 'Omzet hari ini', 'value' => $this->formatCurrency($unitUsahaTodaySales)],
                    ['label' => 'Omzet bulan ini', 'value' => $this->formatCurrency($unitUsahaMonthSales)],
                    ['label' => 'Inventory aktif', 'value' => number_format($activeInventoryCount)],
                    ['label' => 'Stok minimum', 'value' => number_format($lowStockCount)],
                ],
                'shortcuts' => [
                    ['label' => 'Dashboard', 'route' => 'unit-usaha.dashboard'],
                    ['label' => 'Kasir / POS', 'route' => 'unit-usaha.pos'],
                    ['label' => 'Inventory', 'route' => 'unit-usaha.inventory'],
                    ['label' => 'Master Jasa', 'route' => 'unit-usaha.master.services'],
                    ['label' => 'Master Barang', 'route' => 'unit-usaha.master.inventory'],
                    ['label' => 'Pembelian', 'route' => 'unit-usaha.purchases'],
                    ['label' => 'Stock Opname', 'route' => 'unit-usaha.stock-opname'],
                    ['label' => 'Laporan', 'route' => 'unit-usaha.reports'],
                ],
                'visible' => $canViewUnitUsaha,
            ],
            [
                'title' => 'Buku Besar / GL',
                'icon' => 'fas fa-book',
                'accent' => 'indigo',
                'description' => 'Lihat COA, jurnal, posting, ledger, laporan keuangan, dan kontrol akuntansi inti.',
                'stats' => [
                    ['label' => 'Periode aktif', 'value' => $openPeriod ?: 'Belum ada'],
                    ['label' => 'Draft jurnal', 'value' => number_format($draftJournals)],
                    ['label' => 'Posted bulan ini', 'value' => number_format($postedJournalsThisMonth)],
                    ['label' => 'Selisih ledger', 'value' => $this->formatCurrency($trialBalanceGap)],
                ],
                'shortcuts' => [
                    ['label' => 'Dashboard', 'route' => 'gl.dashboard'],
                    ['label' => 'Daftar Akun', 'route' => 'gl.accounts'],
                    ['label' => 'Jurnal Umum', 'route' => 'gl.journals'],
                    ['label' => 'Posting', 'route' => 'gl.posting'],
                    ['label' => 'Mapping', 'route' => 'gl.mappings'],
                    ['label' => 'Buku Besar', 'route' => 'gl.ledgers'],
                    ['label' => 'Neraca Saldo', 'route' => 'gl.trial-balance'],
                    ['label' => 'Laporan Keuangan', 'route' => 'gl.financial-reports'],
                ],
                'visible' => $canViewAdminModules,
            ],
            [
                'title' => 'Piutang Usaha',
                'icon' => 'fas fa-file-invoice-dollar',
                'accent' => 'amber',
                'description' => 'Kelola perusahaan, kontrak, invoice, pembayaran, dan monitoring piutang perusahaan.',
                'stats' => [
                    ['label' => 'Outstanding', 'value' => $this->formatCurrency($totalReceivables)],
                    ['label' => 'Invoice overdue', 'value' => number_format($overdueInvoices)],
                    ['label' => 'Diterima bulan ini', 'value' => $this->formatCurrency($receivedThisMonth)],
                ],
                'shortcuts' => [
                    ['label' => 'Dashboard', 'route' => 'piutang-usaha.dashboard'],
                    ['label' => 'Perusahaan', 'route' => 'piutang-usaha.companies'],
                    ['label' => 'Kontrak', 'route' => 'piutang-usaha.contracts'],
                    ['label' => 'Invoice', 'route' => 'piutang-usaha.invoices'],
                    ['label' => 'Pembayaran', 'route' => 'piutang-usaha.payments'],
                    ['label' => 'Laporan', 'route' => 'piutang-usaha.reports'],
                ],
                'visible' => $canViewAdminModules,
            ],
            [
                'title' => 'Fixed Asset',
                'icon' => 'fas fa-building',
                'accent' => 'teal',
                'description' => 'Pusat ringkasan register aset, depresiasi, mutasi, disposal, dan laporan aset tetap.',
                'stats' => [
                    ['label' => 'Nilai buku aset', 'value' => $this->formatCurrency($bookValueAssets)],
                    ['label' => 'Depresiasi bulan ini', 'value' => $this->formatCurrency($depreciationThisMonth)],
                    ['label' => 'Mendekati akhir umur', 'value' => number_format($assetsNearEndOfLife)],
                    ['label' => 'Disposal bulan ini', 'value' => number_format($disposalsThisMonth)],
                ],
                'shortcuts' => [
                    ['label' => 'Register Aset', 'route' => 'fixed-assets.assets'],
                    ['label' => 'Depresiasi', 'route' => 'fixed-assets.depreciation'],
                    ['label' => 'Mutasi', 'route' => 'fixed-assets.mutations'],
                    ['label' => 'Laporan', 'route' => 'fixed-assets.reports'],
                ],
                'visible' => $canViewUnitUsaha,
            ],
            [
                'title' => 'Data & Sistem',
                'icon' => 'fas fa-shield-halved',
                'accent' => 'slate',
                'description' => 'Master anggota, pengurus, user, posisi, dan pengaturan inti aplikasi.',
                'stats' => [
                    ['label' => 'Total anggota', 'value' => number_format(Member::count())],
                    ['label' => 'Pengurus aktif', 'value' => number_format(Official::where('is_active', true)->count())],
                    ['label' => 'User sistem', 'value' => number_format(User::count())],
                ],
                'shortcuts' => [
                    ['label' => 'Data Anggota', 'route' => 'members'],
                    ['label' => 'Posisi', 'route' => 'settings.positions'],
                    ['label' => 'Pengurus', 'route' => 'settings.officials'],
                    ['label' => 'Users', 'route' => 'settings.users'],
                    ['label' => 'Profil', 'route' => 'profile'],
                ],
                'visible' => $canViewAdminModules,
            ],
        ])->where('visible', true)->values();
    }

    private function getActivityChart(bool $canViewUnitUsaha, bool $canViewAdminModules): array
    {
        $months = [];
        $savingData = [];
        $loanData = [];
        $salesData = [];
        $receivableData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            $savingData[] = (float) Saving::whereYear('deposit_date', $date->year)
                ->whereMonth('deposit_date', $date->month)
                ->sum('amount');

            $loanData[] = (float) Loan::whereYear('disbursement_date', $date->year)
                ->whereMonth('disbursement_date', $date->month)
                ->sum('principal_amount');

            $salesData[] = $canViewUnitUsaha
                ? (float) UnitUsahaSale::whereYear('sale_date', $date->year)
                    ->whereMonth('sale_date', $date->month)
                    ->sum('total_amount')
                : 0.0;

            $receivableData[] = $canViewAdminModules
                ? (float) PiutangUsahaInvoice::whereYear('invoice_date', $date->year)
                    ->whereMonth('invoice_date', $date->month)
                    ->sum('total_amount')
                : 0.0;
        }

        $datasets = [
            [
                'label' => 'Simpanan',
                'data' => $savingData,
                'borderColor' => '#059669',
                'backgroundColor' => 'rgba(5, 150, 105, 0.12)',
            ],
            [
                'label' => 'Pinjaman Dicairkan',
                'data' => $loanData,
                'borderColor' => '#d97706',
                'backgroundColor' => 'rgba(217, 119, 6, 0.12)',
            ],
        ];

        if ($canViewUnitUsaha) {
            $datasets[] = [
                'label' => 'Penjualan Unit Usaha',
                'data' => $salesData,
                'borderColor' => '#e11d48',
                'backgroundColor' => 'rgba(225, 29, 72, 0.12)',
            ];
        }

        if ($canViewAdminModules) {
            $datasets[] = [
                'label' => 'Invoice Piutang',
                'data' => $receivableData,
                'borderColor' => '#4f46e5',
                'backgroundColor' => 'rgba(79, 70, 229, 0.12)',
            ];
        }

        return [
            'months' => $months,
            'datasets' => $datasets,
        ];
    }

    private function getAttentionItems(
        bool $canViewUnitUsaha,
        bool $canViewAdminModules,
        int $lowStockCount,
        int $pendingLoans,
        int $approvedLoans,
        int $overdueInvoices,
        int $draftJournals,
        int $assetsNearEndOfLife,
    ): Collection {
        return collect([
            $pendingLoans > 0 ? [
                'label' => 'Pengajuan pinjaman menunggu proses',
                'detail' => number_format($pendingLoans) . ' pengajuan masih perlu approval.',
                'route' => 'simpan-pinjam.loans.approvals',
                'tone' => 'amber',
            ] : null,
            $approvedLoans > 0 ? [
                'label' => 'Pinjaman siap dicairkan',
                'detail' => number_format($approvedLoans) . ' pinjaman sudah approved dan menunggu pencairan.',
                'route' => 'simpan-pinjam.loans.disbursement',
                'tone' => 'emerald',
            ] : null,
            $canViewUnitUsaha && $lowStockCount > 0 ? [
                'label' => 'Stok minimum terdeteksi',
                'detail' => number_format($lowStockCount) . ' item inventory sudah menyentuh batas minimum.',
                'route' => 'unit-usaha.inventory',
                'tone' => 'rose',
            ] : null,
            $canViewAdminModules && $overdueInvoices > 0 ? [
                'label' => 'Invoice overdue perlu follow-up',
                'detail' => number_format($overdueInvoices) . ' invoice perusahaan sudah melewati jatuh tempo.',
                'route' => 'piutang-usaha.payments',
                'tone' => 'indigo',
            ] : null,
            $canViewAdminModules && $draftJournals > 0 ? [
                'label' => 'Jurnal draft belum diposting',
                'detail' => number_format($draftJournals) . ' jurnal masih pending di proses posting.',
                'route' => 'gl.posting',
                'tone' => 'slate',
            ] : null,
            $canViewUnitUsaha && $assetsNearEndOfLife > 0 ? [
                'label' => 'Aset mendekati akhir umur manfaat',
                'detail' => number_format($assetsNearEndOfLife) . ' aset perlu ditinjau untuk penggantian atau disposal.',
                'route' => 'fixed-assets.reports',
                'tone' => 'teal',
            ] : null,
        ])->filter()->values();
    }

    private function getRecentActivities(bool $canViewUnitUsaha, bool $canViewAdminModules): Collection
    {
        $activities = collect();

        $activities = $activities->concat(
            Saving::with(['member', 'savingType'])
                ->latest('deposit_date')
                ->take(4)
                ->get()
                ->map(fn (Saving $saving) => [
                    'label' => 'Setoran ' . ($saving->savingType?->name ?? 'Simpanan'),
                    'detail' => $saving->member?->name ?? '-',
                    'date' => $saving->deposit_date ?? $saving->created_at,
                    'amount' => (float) $saving->amount,
                    'badge' => 'Simpan Pinjam',
                    'icon' => 'fas fa-piggy-bank',
                    'accent' => 'emerald',
                ])
        );

        $activities = $activities->concat(
            LoanPayment::with('loan.member')
                ->latest('payment_date')
                ->take(4)
                ->get()
                ->map(fn (LoanPayment $payment) => [
                    'label' => 'Pembayaran pinjaman',
                    'detail' => $payment->loan?->member?->name ?? '-',
                    'date' => $payment->payment_date ?? $payment->created_at,
                    'amount' => (float) $payment->total_paid,
                    'badge' => 'Simpan Pinjam',
                    'icon' => 'fas fa-hand-holding-dollar',
                    'accent' => 'amber',
                ])
        );

        if ($canViewUnitUsaha) {
            $activities = $activities->concat(
                UnitUsahaSale::with('member')
                    ->latest('sale_date')
                    ->latest('id')
                    ->take(4)
                    ->get()
                    ->map(fn (UnitUsahaSale $sale) => [
                        'label' => 'Transaksi POS ' . strtoupper((string) $sale->category),
                        'detail' => $sale->member?->name ?: strtoupper((string) $sale->payment_method ?: 'CASH'),
                        'date' => $sale->sale_date ?? $sale->created_at,
                        'amount' => (float) $sale->total_amount,
                        'badge' => 'Unit Usaha',
                        'icon' => 'fas fa-cash-register',
                        'accent' => 'rose',
                    ])
            );

            $activities = $activities->concat(
                FixedAssetDepreciation::with('asset')
                    ->latest('depreciation_date')
                    ->take(3)
                    ->get()
                    ->map(fn (FixedAssetDepreciation $depreciation) => [
                        'label' => 'Depresiasi aset',
                        'detail' => $depreciation->asset?->asset_name ?? '-',
                        'date' => $depreciation->depreciation_date ?? $depreciation->created_at,
                        'amount' => (float) $depreciation->amount,
                        'badge' => 'Fixed Asset',
                        'icon' => 'fas fa-building',
                        'accent' => 'teal',
                    ])
            );
        }

        if ($canViewAdminModules) {
            $activities = $activities->concat(
                PiutangUsahaInvoice::with('company')
                    ->latest('invoice_date')
                    ->take(4)
                    ->get()
                    ->map(fn (PiutangUsahaInvoice $invoice) => [
                        'label' => 'Invoice perusahaan',
                        'detail' => $invoice->company?->name ?? '-',
                        'date' => $invoice->invoice_date ?? $invoice->created_at,
                        'amount' => (float) $invoice->total_amount,
                        'badge' => 'Piutang Usaha',
                        'icon' => 'fas fa-file-invoice-dollar',
                        'accent' => 'indigo',
                    ])
            );

            $activities = $activities->concat(
                JournalEntry::query()
                    ->latest('entry_date')
                    ->latest('id')
                    ->take(3)
                    ->get()
                    ->map(fn (JournalEntry $journal) => [
                        'label' => 'Jurnal ' . strtoupper((string) $journal->status),
                        'detail' => $journal->reference_number ?: ($journal->memo ?: 'Jurnal umum'),
                        'date' => $journal->entry_date ?? $journal->created_at,
                        'amount' => (float) $journal->amount,
                        'badge' => 'GL',
                        'icon' => 'fas fa-book',
                        'accent' => 'slate',
                    ])
            );
        }

        return $activities
            ->sortByDesc('date')
            ->take(12)
            ->values();
    }

    private function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
