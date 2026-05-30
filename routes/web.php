<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FixedAssetController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MemberWebController;
use App\Http\Controllers\PiutangUsahaController;
use App\Http\Controllers\SavingLoanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UnitUsahaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('home');
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::view('/profile', 'profile')->name('profile');

    Route::middleware('login.context:pengurus')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/simpan-pinjam', [SavingLoanController::class, 'index'])->name('simpan-pinjam');
        Route::get('/simpan-pinjam/pinjaman/pengajuan', [SavingLoanController::class, 'loansApplications'])->name('simpan-pinjam.loans.applications');
        Route::get('/simpan-pinjam/pinjaman/approval', [SavingLoanController::class, 'loansApprovals'])->name('simpan-pinjam.loans.approvals');
        Route::get('/simpan-pinjam/pinjaman/pencairan', [SavingLoanController::class, 'loansDisbursement'])->name('simpan-pinjam.loans.disbursement');
        Route::get('/simpan-pinjam/pinjaman/monitoring', [SavingLoanController::class, 'loansMonitoring'])->name('simpan-pinjam.loans.monitoring');
        Route::get('/simpan-pinjam/pinjaman/selesai', [SavingLoanController::class, 'loansCompleted'])->name('simpan-pinjam.loans.completed');
        Route::get('/simpan-pinjam/angsuran', [SavingLoanController::class, 'installments'])->name('simpan-pinjam.installments');
        Route::get('/simpan-pinjam/simpanan', [SavingLoanController::class, 'savings'])->name('simpan-pinjam.savings');
        Route::post('/simpan-pinjam/savings', [SavingLoanController::class, 'storeSaving'])->name('simpan-pinjam.saving.store');
        Route::post('/simpan-pinjam/loans', [SavingLoanController::class, 'storeLoan'])->name('simpan-pinjam.loan.store');
        Route::post('/simpan-pinjam/loans/{loan}/approval', [SavingLoanController::class, 'processApproval'])->name('simpan-pinjam.loan.approval.process');
        Route::post('/simpan-pinjam/loans/{loan}/disburse', [SavingLoanController::class, 'processDisbursement'])->name('simpan-pinjam.loan.disburse.process');
        Route::post('/simpan-pinjam/payments', [SavingLoanController::class, 'storePayment'])->name('simpan-pinjam.payment.store');
        Route::middleware('unit.usaha.only')->prefix('fixed-aset-depresiasi')->name('fixed-assets.')->group(function () {
            Route::redirect('/', '/fixed-aset-depresiasi/fixed-aset')->name('index');
            Route::get('/fixed-aset', [FixedAssetController::class, 'assets'])->name('assets');
            Route::post('/fixed-aset', [FixedAssetController::class, 'storeAsset'])->name('assets.store');
            Route::put('/fixed-aset/{asset}', [FixedAssetController::class, 'updateAsset'])->name('assets.update');
            Route::get('/depresiasi', [FixedAssetController::class, 'depreciation'])->name('depreciation');
            Route::post('/depresiasi', [FixedAssetController::class, 'runDepreciation'])->name('depreciation.run');
            Route::post('/depresiasi/{depreciation}/posting', [FixedAssetController::class, 'postDepreciation'])->name('depreciation.post');
            Route::get('/mutasi-aset', [FixedAssetController::class, 'mutations'])->name('mutations');
            Route::post('/mutasi-aset', [FixedAssetController::class, 'storeMutation'])->name('mutations.store');
            Route::get('/laporan-fa', [FixedAssetController::class, 'reports'])->name('reports');
            Route::get('/laporan-fa/export/excel', [FixedAssetController::class, 'exportReportsExcel'])->name('reports.export.excel');
        });

        Route::middleware('unit.usaha.only')->group(function () {
            Route::prefix('unit-usaha')->name('unit-usaha.')->group(function () {
                Route::get('/', [UnitUsahaController::class, 'dashboard'])->name('dashboard');
                Route::get('/kasir-pos', [UnitUsahaController::class, 'pos'])->name('pos');
                Route::post('/kasir-pos', [UnitUsahaController::class, 'storePos'])->name('pos.store');
                Route::get('/stok-inventory', [UnitUsahaController::class, 'inventory'])->name('inventory');
                Route::get('/master-produk-jasa', [UnitUsahaController::class, 'products'])->name('products');
                Route::get('/master-produk-jasa/jasa-service', [UnitUsahaController::class, 'masterServices'])->name('master.services');
                Route::post('/master-produk-jasa/jasa-service', [UnitUsahaController::class, 'storeMasterService'])->name('master.services.store');
                Route::get('/master-produk-jasa/inventory-atk', [UnitUsahaController::class, 'masterInventory'])->name('master.inventory');
                Route::post('/master-produk-jasa/inventory-atk', [UnitUsahaController::class, 'storeMasterInventory'])->name('master.inventory.store');
                Route::get('/pembelian-barang-masuk', [UnitUsahaController::class, 'purchases'])->name('purchases');
                Route::post('/pembelian-barang-masuk', [UnitUsahaController::class, 'storePurchase'])->name('purchases.store');
                Route::get('/stock-opname', [UnitUsahaController::class, 'stockOpname'])->name('stock-opname');
                Route::post('/stock-opname', [UnitUsahaController::class, 'storeStockOpname'])->name('stock-opname.store');
                Route::get('/laporan', [UnitUsahaController::class, 'reports'])->name('reports');
            });

            Route::redirect('/retail', '/unit-usaha')->name('retail');
        });

        Route::get('/accounting', function () {
            return view('accounting');
        })->name('accounting');
        Route::middleware('administrator.only')->group(function () {
            Route::prefix('buku-besar-gl')->name('gl.')->group(function () {
                Route::get('/', [GeneralLedgerController::class, 'dashboard'])->name('dashboard');
                Route::post('/setup-defaults', [GeneralLedgerController::class, 'setupDefaults'])->name('setup-defaults');
                Route::post('/maintenance/sync-legacy', [GeneralLedgerController::class, 'syncLegacyData'])->name('maintenance.sync-legacy');
                Route::get('/daftar-akun-coa', [GeneralLedgerController::class, 'accounts'])->name('accounts');
                Route::post('/daftar-akun-coa', [GeneralLedgerController::class, 'storeAccount'])->name('accounts.store');
                Route::put('/daftar-akun-coa/{account}', [GeneralLedgerController::class, 'updateAccount'])->name('accounts.update');
                Route::get('/kelompok-akun', [GeneralLedgerController::class, 'accountGroups'])->name('account-groups');
                Route::post('/kelompok-akun', [GeneralLedgerController::class, 'storeAccountGroup'])->name('account-groups.store');
                Route::get('/periode-akuntansi', [GeneralLedgerController::class, 'periods'])->name('periods');
                Route::post('/periode-akuntansi', [GeneralLedgerController::class, 'storePeriod'])->name('periods.store');
                Route::get('/jurnal-umum', [GeneralLedgerController::class, 'journals'])->name('journals');
                Route::post('/jurnal-umum', [GeneralLedgerController::class, 'storeJournal'])->name('journals.store');
                Route::post('/jurnal-umum/setup-samples', [GeneralLedgerController::class, 'setupSampleJournals'])->name('journals.setup-samples');
                Route::get('/posting-jurnal', [GeneralLedgerController::class, 'posting'])->name('posting');
                Route::post('/posting-jurnal/{journal}', [GeneralLedgerController::class, 'processPosting'])->name('posting.process');
                Route::get('/mapping-akun', [GeneralLedgerController::class, 'mappings'])->name('mappings');
                Route::post('/mapping-akun', [GeneralLedgerController::class, 'storeMapping'])->name('mappings.store');
                Route::post('/mapping-akun/setup-defaults', [GeneralLedgerController::class, 'setupDefaultMappings'])->name('mappings.setup-defaults');
                Route::get('/pusat-biaya', [GeneralLedgerController::class, 'costCenters'])->name('cost-centers');
                Route::post('/pusat-biaya', [GeneralLedgerController::class, 'storeCostCenter'])->name('cost-centers.store');
                Route::get('/buku-besar', [GeneralLedgerController::class, 'ledgers'])->name('ledgers');
                Route::get('/neraca-saldo', [GeneralLedgerController::class, 'trialBalance'])->name('trial-balance');
                Route::get('/laporan-keuangan', [GeneralLedgerController::class, 'financialReports'])->name('financial-reports');
                Route::get('/tutup-buku', [GeneralLedgerController::class, 'closing'])->name('closing');
                Route::post('/tutup-buku', [GeneralLedgerController::class, 'processClosing'])->name('closing.process');
                Route::get('/audit-jurnal', [GeneralLedgerController::class, 'audit'])->name('audit');
            });

            Route::prefix('piutang-usaha')->name('piutang-usaha.')->group(function () {
                Route::get('/', [PiutangUsahaController::class, 'dashboard'])->name('dashboard');
                Route::get('/data-perusahaan', [PiutangUsahaController::class, 'companies'])->name('companies');
                Route::post('/data-perusahaan', [PiutangUsahaController::class, 'storeCompany'])->name('companies.store');
                Route::get('/kontrak-jasa', [PiutangUsahaController::class, 'contracts'])->name('contracts');
                Route::post('/kontrak-jasa', [PiutangUsahaController::class, 'storeContract'])->name('contracts.store');
                Route::get('/tagihan-invoice', [PiutangUsahaController::class, 'invoices'])->name('invoices');
                Route::post('/tagihan-invoice', [PiutangUsahaController::class, 'storeInvoice'])->name('invoices.store');
                Route::get('/pembayaran-piutang', [PiutangUsahaController::class, 'payments'])->name('payments');
                Route::post('/pembayaran-piutang', [PiutangUsahaController::class, 'storePayment'])->name('payments.store');
                Route::get('/laporan', [PiutangUsahaController::class, 'reports'])->name('reports');
            });

            Route::get('/members', [MemberWebController::class, 'index'])->name('members');
            Route::get('/members/export/csv', [MemberWebController::class, 'exportCsv'])->name('members.export.csv');
            Route::get('/members/export/excel', [MemberWebController::class, 'exportExcel'])->name('members.export.excel');
            Route::get('/members/create', [MemberWebController::class, 'create'])->name('members.create');
            Route::post('/members', [MemberWebController::class, 'store'])->name('members.store');
            Route::get('/members/{member}/edit', [MemberWebController::class, 'edit'])->name('members.edit');
            Route::put('/members/{member}', [MemberWebController::class, 'update'])->name('members.update');
            Route::post('/members/{member}/retail-receivable-payments', [MemberWebController::class, 'storeReceivablePayment'])->name('members.retail-receivable-payments.store');

            Route::get('/settings/positions', [SettingsController::class, 'positions'])->name('settings.positions');
            Route::post('/settings/positions', [SettingsController::class, 'storePosition'])->name('settings.positions.store');
            Route::put('/settings/positions/{position}', [SettingsController::class, 'updatePosition'])->name('settings.positions.update');
            Route::get('/settings/officials', [SettingsController::class, 'officials'])->name('settings.officials');
            Route::get('/settings/officials/export/excel', [SettingsController::class, 'exportOfficialsExcel'])->name('settings.officials.export.excel');
            Route::post('/settings/officials', [SettingsController::class, 'storeOfficial'])->name('settings.officials.store');
            Route::put('/settings/officials/{official}', [SettingsController::class, 'updateOfficial'])->name('settings.officials.update');
            Route::get('/settings/users', [SettingsController::class, 'users'])->name('settings.users');
            Route::post('/settings/users', [SettingsController::class, 'storeUser'])->name('settings.users.store');
            Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])->name('settings.users.update');
            Route::view('/reports', 'reports')->name('reports');
        });
    });

    Route::middleware('login.context:anggota')->group(function () {
        Route::get('/member-portal', [MemberPortalController::class, 'index'])->name('member-portal');
        Route::get('/member-portal/pinjaman', [MemberPortalController::class, 'loans'])->name('member-portal.loans');
        Route::get('/member-portal/pinjaman/ajukan', [MemberPortalController::class, 'createLoan'])->name('member-portal.loans.create');
        Route::post('/member-portal/pinjaman', [MemberPortalController::class, 'storeLoan'])->name('member-portal.loans.store');
        Route::get('/member-portal/pinjaman/dokumen/{document}', [MemberPortalController::class, 'downloadDocument'])->name('member-portal.loans.documents.download');
    });
});
