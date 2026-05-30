<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\UnitUsahaInventory;
use App\Models\UnitUsahaPurchase;
use App\Models\UnitUsahaPurchaseItem;
use App\Models\UnitUsahaSale;
use App\Models\UnitUsahaSaleItem;
use App\Models\UnitUsahaService;
use App\Models\UnitUsahaStockOpname;
use App\Models\UnitUsahaStockOpnameItem;
use App\Services\JournalService;
use App\Services\RetailInventoryBridgeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitUsahaController extends Controller
{
    public function __construct(
        private JournalService $journalService,
        private RetailInventoryBridgeService $retailInventoryBridgeService
    ) {}

    public function dashboard(): View
    {
        $today = now()->toDateString();
        $this->retailInventoryBridgeService->syncAllRetailItemsToInventories();
        $inventories = UnitUsahaInventory::query()->where('is_active', true)->orderBy('name')->get();

        return view('unit-usaha.dashboard', [
            'title' => 'Dashboard Unit Usaha - Koperasi Digital Mandiri',
            'sectionLabel' => 'Dashboard Unit Usaha',
            'pageTitle' => 'Dashboard Unit Usaha',
            'pageDescription' => 'Ringkasan operasional harian unit usaha koperasi, termasuk transaksi, stok, dan item yang perlu perhatian.',
            'todaySales' => UnitUsahaSale::query()->with('items')->whereDate('sale_date', $today)->get(),
            'todayPurchases' => UnitUsahaPurchase::query()->whereDate('purchase_date', $today)->get(),
            'recentSales' => UnitUsahaSale::query()->with('items')->latest('sale_date')->latest('id')->take(5)->get(),
            'activeInventoryCount' => $inventories->count(),
            'activeServiceCount' => UnitUsahaService::query()->where('is_active', true)->count(),
            'lowStockItems' => $inventories->filter(fn ($item) => $item->stock <= $item->minimum_stock)->values(),
        ]);
    }

    public function pos(): View
    {
        $serviceOptionsJson = json_encode(
            UnitUsahaService::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn ($service) => [
                    'id' => $service->id,
                    'code' => $service->code,
                    'name' => $service->name,
                    'price' => (float) $service->price,
                ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $inventoryItems = UnitUsahaInventory::query()->where('is_active', true)->orderBy('name')->get();
        $inventoryOptionsJson = json_encode(
            $inventoryItems->map(fn ($inventory) => [
                'id' => $inventory->id,
                'code' => $inventory->code,
                'name' => $inventory->name,
                'price' => (float) $inventory->selling_price,
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return view('unit-usaha.pos', [
            'title' => 'Kasir / POS - Koperasi Digital Mandiri',
            'sectionLabel' => 'Kasir / POS',
            'pageTitle' => 'Kasir / POS',
            'pageDescription' => 'Transaksi kasir toko umum untuk penjualan jasa dan barang unit usaha.',
            'sales' => UnitUsahaSale::query()->with(['items', 'member'])->latest('sale_date')->latest('id')->get(),
            'saleNumber' => $this->generateSaleNumber(),
            'serviceOptionsJson' => $serviceOptionsJson,
            'inventoryOptionsJson' => $inventoryOptionsJson,
            'members' => Member::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function storePos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_number' => ['required', 'string', 'max:100', 'unique:unit_usaha_sales,sale_number'],
            'sale_date' => ['required', 'date'],
            'category' => ['required', 'in:indomaret,photocopy'],
            'payment_method' => ['required', 'in:cash,salary_cut'],
            'member_id' => ['nullable', 'exists:members,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,inventory'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validated['payment_method'] === 'salary_cut' && empty($validated['member_id'])) {
            return back()->withInput()->withErrors([
                'member_id' => 'Anggota wajib dipilih jika pembayaran menggunakan potong gaji.',
            ]);
        }

        DB::transaction(function () use ($validated) {
            $member = null;
            $mappingPaymentMethod = str_replace('_', '-', $validated['payment_method']);
            if (!empty($validated['member_id'])) {
                $member = Member::query()->lockForUpdate()->findOrFail($validated['member_id']);
                if ($member->status !== 'active') {
                    abort(422, 'Anggota yang dipilih tidak aktif.');
                }
            }

            $sale = UnitUsahaSale::create([
                'sale_number' => $validated['sale_number'],
                'sale_date' => $validated['sale_date'],
                'member_id' => $member?->id,
                'category' => $validated['category'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
                'status' => 'posted',
                'is_posted' => false,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $subtotal = (int) $item['quantity'] * (float) $item['unit_price'];
                $totalAmount += $subtotal;

                $serviceId = null;
                $inventoryId = null;
                $itemName = '';

                if ($item['item_type'] === 'service') {
                    $service = UnitUsahaService::query()->findOrFail($item['item_id']);
                    $serviceId = $service->id;
                    $itemName = $service->name;
                } else {
                    $inventory = UnitUsahaInventory::query()->findOrFail($item['item_id']);
                    if ($inventory->stock < (int) $item['quantity']) {
                        abort(422, 'Stok barang ' . $inventory->name . ' tidak mencukupi.');
                    }
                    $inventory->decrement('stock', (int) $item['quantity']);
                    $inventoryId = $inventory->id;
                    $itemName = $inventory->name;
                }

                UnitUsahaSaleItem::create([
                    'sale_id' => $sale->id,
                    'item_type' => $item['item_type'],
                    'service_id' => $serviceId,
                    'inventory_id' => $inventoryId,
                    'item_name' => $itemName,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update(['total_amount' => $totalAmount]);

            $journal = $this->journalService->createMappedJournal(
                sourceModule: 'unit-usaha',
                transactionType: 'retail-sale-' . $validated['category'] . '-' . $mappingPaymentMethod,
                referenceType: 'unit_usaha_sale',
                referenceId: $sale->id,
                amount: $totalAmount,
                memo: 'Transaksi POS ' . $sale->sale_number . ' - ' . ucfirst($validated['category'])
            );

            if ($validated['payment_method'] === 'salary_cut' && $member) {
                $member->increment('balance_receivable', $totalAmount);

                $this->journalService->updateMemberLedger(
                    memberId: $member->id,
                    ledgerScope: 'retail_receivable',
                    transactionType: 'unit_usaha_sale',
                    transactionId: $sale->id,
                    debit: $totalAmount,
                    credit: 0,
                    memo: 'Transaksi POS ' . ucfirst($validated['category'])
                );
            }

            $sale->update([
                'is_posted' => true,
                'journal_entry_id' => $journal->id,
            ]);
        });

        return redirect()->route('unit-usaha.pos')->with('success', 'Transaksi POS berhasil disimpan.');
    }

    public function inventory(): View
    {
        $inventories = UnitUsahaInventory::query()->orderBy('name')->get();

        return view('unit-usaha.inventory', [
            'title' => 'Stok & Inventory - Koperasi Digital Mandiri',
            'sectionLabel' => 'Stok & Inventory',
            'pageTitle' => 'Stok & Inventory',
            'pageDescription' => 'Pantau ketersediaan stok barang, bahan baku, dan kebutuhan operasional unit usaha.',
            'inventories' => $inventories,
            'activeInventoryCount' => $inventories->where('is_active', true)->count(),
            'lowStockItems' => $inventories->filter(fn ($item) => $item->stock <= $item->minimum_stock)->values(),
        ]);
    }

    public function products(): View
    {
        return $this->masterServices();
    }

    public function masterServices(): View
    {
        return view('unit-usaha.master-services', [
            'title' => 'JASA/SERVICE - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Produk/Jasa',
            'pageTitle' => 'JASA/SERVICE',
            'pageDescription' => 'Kelola layanan jasa unit usaha seperti fotokopi, print, jilid, laminating, dan layanan operasional lain.',
            'headerTabs' => $this->masterTabs('unit-usaha.master.services'),
            'services' => UnitUsahaService::query()->latest()->get(),
        ]);
    }

    public function storeMasterService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:unit_usaha_services,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        UnitUsahaService::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('unit-usaha.master.services')
            ->with('success', 'Jasa/service berhasil ditambahkan.');
    }

    public function masterInventory(): View
    {
        return view('unit-usaha.master-inventory', [
            'title' => 'INVENTORY/ATK - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Produk/Jasa',
            'pageTitle' => 'INVENTORY/ATK',
            'pageDescription' => 'Kelola master barang inventory, ATK, bahan baku, dan item fisik yang dipantau stoknya di unit usaha.',
            'headerTabs' => $this->masterTabs('unit-usaha.master.inventory'),
            'inventories' => UnitUsahaInventory::query()->latest()->get(),
        ]);
    }

    public function storeMasterInventory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:unit_usaha_inventories,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        UnitUsahaInventory::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'stock' => 0,
            'minimum_stock' => $validated['minimum_stock'],
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('unit-usaha.master.inventory')
            ->with('success', 'Inventory/ATK berhasil ditambahkan.');
    }

    public function purchases(): View
    {
        return view('unit-usaha.purchases', [
            'title' => 'Pembelian / Barang Masuk - Koperasi Digital Mandiri',
            'sectionLabel' => 'Pembelian / Barang Masuk',
            'pageTitle' => 'Pembelian / Barang Masuk',
            'pageDescription' => 'Catat pembelian barang, bahan baku masuk, dan pemasok yang mendukung operasional unit usaha.',
            'inventories' => UnitUsahaInventory::query()->where('is_active', true)->orderBy('name')->get(),
            'purchases' => UnitUsahaPurchase::query()->with('items.inventory')->latest('purchase_date')->latest('id')->get(),
            'purchaseNumber' => $this->generatePurchaseNumber(),
        ]);
    }

    public function storePurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_number' => ['required', 'string', 'max:100', 'unique:unit_usaha_purchases,purchase_number'],
            'purchase_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,credit'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'exists:unit_usaha_inventories,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $totalAmount = 0;

            $purchase = UnitUsahaPurchase::create([
                'purchase_number' => $validated['purchase_number'],
                'purchase_date' => $validated['purchase_date'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
                'payment_method' => $validated['payment_method'],
                'status' => 'posted',
                'is_posted' => false,
            ]);

            foreach ($validated['items'] as $item) {
                $subtotal = (int) $item['quantity'] * (float) $item['unit_price'];
                $totalAmount += $subtotal;

                UnitUsahaPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'inventory_id' => $item['inventory_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                $inventory = UnitUsahaInventory::query()->findOrFail($item['inventory_id']);
                $inventory->increment('stock', (int) $item['quantity']);
                $inventory->update([
                    'purchase_price' => $item['unit_price'],
                ]);
                $this->retailInventoryBridgeService->syncRetailItemsFromInventory($inventory->fresh());
            }

            $journal = $this->journalService->createJournal(
                referenceType: 'unit_usaha_purchase',
                referenceId: $purchase->id,
                debitAccountCode: '1201',
                creditAccountCode: $validated['payment_method'] === 'cash' ? '1001' : '2001',
                amount: $totalAmount,
                memo: 'Pembelian barang masuk ' . $purchase->purchase_number
            );

            $purchase->update([
                'total_amount' => $totalAmount,
                'is_posted' => true,
                'journal_entry_id' => $journal->id,
            ]);
        });

        return redirect()
            ->route('unit-usaha.purchases')
            ->with('success', 'Pembelian / barang masuk berhasil disimpan dan stok telah diperbarui.');
    }

    public function stockOpname(): View
    {
        $inventoryItems = UnitUsahaInventory::query()->where('is_active', true)->orderBy('name')->get();
        $inventoryOptionsJson = json_encode(
            $inventoryItems->map(fn ($inventory) => [
                'id' => $inventory->id,
                'code' => $inventory->code,
                'name' => $inventory->name,
                'stock' => (int) $inventory->stock,
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return view('unit-usaha.stock-opname', [
            'title' => 'Stock Opname - Koperasi Digital Mandiri',
            'sectionLabel' => 'Stock Opname',
            'pageTitle' => 'Stock Opname',
            'pageDescription' => 'Lakukan pencocokan stok fisik dengan data sistem untuk menjaga akurasi inventory.',
            'inventories' => $inventoryItems,
            'inventoryOptionsJson' => $inventoryOptionsJson,
            'stockOpnames' => UnitUsahaStockOpname::query()->with('items.inventory')->latest('opname_date')->latest('id')->get(),
            'opnameNumber' => $this->generateOpnameNumber(),
        ]);
    }

    public function storeStockOpname(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opname_number' => ['required', 'string', 'max:100', 'unique:unit_usaha_stock_opnames,opname_number'],
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_id' => ['required', 'exists:unit_usaha_inventories,id'],
            'items.*.physical_stock' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $opname = UnitUsahaStockOpname::create([
                'opname_number' => $validated['opname_number'],
                'opname_date' => $validated['opname_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'posted',
                'adjustment_value' => 0,
                'is_posted' => false,
            ]);
            $netAdjustmentValue = 0;

            foreach ($validated['items'] as $item) {
                $inventory = UnitUsahaInventory::query()->findOrFail($item['inventory_id']);
                $systemStock = (int) $inventory->stock;
                $physicalStock = (int) $item['physical_stock'];
                $difference = $physicalStock - $systemStock;
                $netAdjustmentValue += $difference * (float) $inventory->purchase_price;

                UnitUsahaStockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'inventory_id' => $inventory->id,
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference' => $difference,
                    'notes' => $item['notes'] ?? null,
                ]);

                $inventory->update([
                    'stock' => $physicalStock,
                ]);
                $this->retailInventoryBridgeService->syncRetailItemsFromInventory($inventory->fresh());
            }

            $journal = null;
            if ($netAdjustmentValue > 0) {
                $journal = $this->journalService->createJournal(
                    referenceType: 'unit_usaha_stock_opname',
                    referenceId: $opname->id,
                    debitAccountCode: '1201',
                    creditAccountCode: '4001',
                    amount: abs($netAdjustmentValue),
                    memo: 'Penyesuaian stock opname surplus ' . $opname->opname_number
                );
            } elseif ($netAdjustmentValue < 0) {
                $journal = $this->journalService->createJournal(
                    referenceType: 'unit_usaha_stock_opname',
                    referenceId: $opname->id,
                    debitAccountCode: '5002',
                    creditAccountCode: '1201',
                    amount: abs($netAdjustmentValue),
                    memo: 'Penyesuaian stock opname selisih kurang ' . $opname->opname_number
                );
            }

            $opname->update([
                'adjustment_value' => $netAdjustmentValue,
                'is_posted' => $journal !== null,
                'journal_entry_id' => $journal?->id,
            ]);
        });

        return redirect()
            ->route('unit-usaha.stock-opname')
            ->with('success', 'Stock opname berhasil disimpan dan stok master telah disesuaikan.');
    }

    public function fixedAssets(): View
    {
        return view('unit-usaha.fixed-assets', [
            'title' => 'Fixed Aset - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Fixed Aset',
            'pageDescription' => 'Kelola data aset tetap koperasi seperti kendaraan, peralatan, inventaris kantor, dan aset operasional lain.',
            'headerTabs' => $this->fixedAssetTabs('fixed-assets.assets'),
        ]);
    }

    public function depreciation(): View
    {
        return view('unit-usaha.depreciation', [
            'title' => 'Depresiasi - Koperasi Digital Mandiri',
            'sectionLabel' => 'Fixed Aset & Depresiasi',
            'pageTitle' => 'Depresiasi',
            'pageDescription' => 'Pantau perhitungan penyusutan aset tetap per periode dan siapkan integrasi ke jurnal akuntansi.',
            'headerTabs' => $this->fixedAssetTabs('fixed-assets.depreciation'),
        ]);
    }

    public function reports(): View
    {
        $sales = UnitUsahaSale::query()->with('items')->latest('sale_date')->latest('id')->get();
        $purchases = UnitUsahaPurchase::query()->latest('purchase_date')->latest('id')->get();
        $stockOpnames = UnitUsahaStockOpname::query()->with('items')->latest('opname_date')->latest('id')->get();

        return view('unit-usaha.reports', [
            'title' => 'Laporan Unit Usaha - Koperasi Digital Mandiri',
            'sectionLabel' => 'Laporan Unit Usaha',
            'pageTitle' => 'Laporan Unit Usaha',
            'pageDescription' => 'Akses laporan penjualan, inventory, dan performa operasional unit usaha koperasi.',
            'sales' => $sales,
            'purchases' => $purchases,
            'stockOpnames' => $stockOpnames,
            'adjustedItemCount' => $stockOpnames->flatMap->items->filter(fn ($item) => $item->difference !== 0)->count(),
        ]);
    }

    private function renderSection(
        string $routeName,
        string $sectionLabel,
        string $pageTitle,
        string $pageDescription,
        ?array $sections = null,
        ?array $headerTabs = null
    ): View {
        $sections ??= [
            [
                'route' => 'unit-usaha.dashboard',
                'label' => 'Dashboard Unit Usaha',
                'icon' => 'fas fa-chart-line',
                'summary' => 'Ringkasan transaksi, omzet, dan perhatian utama harian.',
            ],
            [
                'route' => 'unit-usaha.pos',
                'label' => 'Kasir / POS',
                'icon' => 'fas fa-cash-register',
                'summary' => 'Transaksi penjualan anggota dan non-anggota.',
            ],
            [
                'route' => 'unit-usaha.inventory',
                'label' => 'Stok & Inventory',
                'icon' => 'fas fa-boxes-stacked',
                'summary' => 'Pemantauan stok, bahan baku, dan ketersediaan barang.',
            ],
            [
                'route' => 'unit-usaha.products',
                'label' => 'Master Produk/Jasa',
                'icon' => 'fas fa-tags',
                'summary' => 'Pengaturan produk, jasa, kategori, harga, dan satuan.',
            ],
            [
                'route' => 'unit-usaha.purchases',
                'label' => 'Pembelian / Barang Masuk',
                'icon' => 'fas fa-truck-ramp-box',
                'summary' => 'Pencatatan supplier, pembelian, dan penerimaan barang.',
            ],
            [
                'route' => 'unit-usaha.stock-opname',
                'label' => 'Stock Opname',
                'icon' => 'fas fa-clipboard-check',
                'summary' => 'Penyesuaian stok berdasarkan hasil pengecekan fisik.',
            ],
            [
                'route' => 'unit-usaha.reports',
                'label' => 'Laporan Unit Usaha',
                'icon' => 'fas fa-file-invoice',
                'summary' => 'Rekap penjualan, stok, dan performa operasional.',
            ],
        ];

        $headerTabs ??= collect($sections)
            ->map(fn (array $section) => [
                'route' => $section['route'],
                'label' => $section['label'],
                'active' => $section['route'] === $routeName,
            ])
            ->all();

        return view('unit-usaha.section', [
            'title' => $pageTitle . ' - Koperasi Digital Mandiri',
            'sectionLabel' => $sectionLabel,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'headerTabs' => $headerTabs,
            'currentRouteName' => $routeName,
            'sections' => $sections,
        ]);
    }

    private function masterSections(): array
    {
        return [
            [
                'route' => 'unit-usaha.master.services',
                'label' => 'JASA/SERVICE',
                'icon' => 'fas fa-screwdriver-wrench',
                'summary' => 'Layanan jasa seperti fotokopi, print, jilid, dan laminating.',
            ],
            [
                'route' => 'unit-usaha.master.inventory',
                'label' => 'INVENTORY/ATK',
                'icon' => 'fas fa-boxes-stacked',
                'summary' => 'Barang persediaan, ATK, bahan baku, dan item inventory lain.',
            ],
        ];
    }

    private function masterTabs(string $activeRoute): array
    {
        return collect($this->masterSections())
            ->map(fn (array $section) => [
                'route' => $section['route'],
                'label' => $section['label'],
                'active' => $section['route'] === $activeRoute,
            ])
            ->all();
    }

    private function fixedAssetTabs(string $activeRoute): array
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
        ];
    }

    private function generatePurchaseNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = UnitUsahaPurchase::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'PBM-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    private function generateSaleNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = UnitUsahaSale::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'POS-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }

    private function generateOpnameNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $countToday = UnitUsahaStockOpname::query()
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;

        return 'OPN-' . $datePrefix . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);
    }
}
