<?php

namespace App\Http\Controllers;

use App\Models\UnitUsahaInventory;
use App\Models\UnitUsahaInventoryCategory;
use App\Models\UnitUsahaInventoryPriceHistory;
use App\Models\UnitUsahaPurchase;
use App\Models\UnitUsahaPurchaseItem;
use App\Models\UnitUsahaSale;
use App\Models\UnitUsahaSaleItem;
use App\Models\UnitUsahaService;
use App\Models\UnitUsahaStockOpname;
use App\Models\UnitUsahaStockOpnameItem;
use App\Services\JournalService;
use App\Services\RetailInventoryBridgeService;
use Carbon\Carbon;
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
        $inventories = UnitUsahaInventory::query()->with('categoryRelation')->where('is_active', true)->orderBy('name')->get();

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
        $filterDate = request('filter_date', now()->toDateString());
        $editSaleId = request('edit');

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
                    'unit' => $service->unit,
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
                'stock' => (int) $inventory->stock,
                'unit' => $inventory->unit,
            ])->values()->all(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $sales = UnitUsahaSale::query()
            ->with(['items', 'member'])
            ->latest('sale_date')
            ->latest('id')
            ->get();

        $filteredSales = $sales
            ->filter(fn (UnitUsahaSale $sale) => optional($sale->sale_date)->toDateString() === $filterDate)
            ->values();

        $editingSale = null;
        $editBlockedMessage = null;

        if ($editSaleId) {
            $candidate = $sales->firstWhere('id', (int) $editSaleId);

            if ($candidate && $this->saleCanBeEdited($candidate)) {
                $editingSale = $candidate;
            } elseif ($candidate) {
                $editBlockedMessage = 'Transaksi tersebut tidak bisa diedit karena sudah diposting atau tanggalnya bukan hari ini.';
            }
        }

        $formItems = old('items');
        if (! is_array($formItems)) {
            $formItems = $editingSale
                ? $editingSale->items->map(fn (UnitUsahaSaleItem $item) => [
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_type === 'service' ? $item->service_id : $item->inventory_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])->values()->all()
                : [['item_type' => 'inventory', 'item_id' => '', 'quantity' => 1, 'unit_price' => 0]];
        }

        return view('unit-usaha.pos', [
            'title' => 'Kasir / POS - Koperasi Digital Mandiri',
            'sectionLabel' => 'Kasir / POS',
            'pageTitle' => 'Kasir / POS',
            'pageDescription' => 'Transaksi kasir toko umum untuk penjualan jasa dan barang unit usaha.',
            'sales' => $sales,
            'filteredSales' => $filteredSales,
            'saleNumber' => $editingSale?->sale_number ?? $this->generateSaleNumber(),
            'serviceOptionsJson' => $serviceOptionsJson,
            'inventoryOptionsJson' => $inventoryOptionsJson,
            'filterDate' => $filterDate,
            'editingSale' => $editingSale,
            'editBlockedMessage' => $editBlockedMessage,
            'formItems' => $formItems,
        ]);
    }

    public function storePos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_number' => ['required', 'string', 'max:100', 'unique:unit_usaha_sales,sale_number'],
            'sale_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,inventory'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $category = 'indomaret';
            $paymentMethod = $validated['payment_method'];

            $sale = UnitUsahaSale::create([
                'sale_number' => $validated['sale_number'],
                'sale_date' => $validated['sale_date'],
                'member_id' => null,
                'category' => $category,
                'payment_method' => $paymentMethod,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => 0,
                'status' => 'draft',
                'is_posted' => false,
                'source_module' => 'unit-usaha',
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $unitPrice = $this->resolveSaleItemPrice($item['item_type'], (int) $item['item_id']);
                $subtotal = (int) $item['quantity'] * $unitPrice;
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
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update(['total_amount' => $totalAmount]);
        });

        return redirect()
            ->route('unit-usaha.pos', ['filter_date' => $validated['sale_date'], 'show_history' => 1])
            ->with('success', 'Transaksi POS berhasil disimpan sebagai draft harian.');
    }

    public function updatePos(Request $request, UnitUsahaSale $sale): RedirectResponse
    {
        if (! $this->saleCanBeEdited($sale)) {
            return redirect()
                ->route('unit-usaha.pos', ['filter_date' => $sale->sale_date?->toDateString(), 'show_history' => 1])
                ->withErrors(['sale' => 'Transaksi POS ini tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'sale_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,inventory'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validated['sale_date'] !== now()->toDateString()) {
            return back()->withErrors([
                'sale_date' => 'Transaksi draft hanya bisa diedit untuk tanggal hari ini.',
            ])->withInput();
        }

        DB::transaction(function () use ($sale, $validated) {
            foreach ($sale->items as $existingItem) {
                if ($existingItem->item_type === 'inventory' && $existingItem->inventory_id) {
                    UnitUsahaInventory::query()
                        ->find($existingItem->inventory_id)
                        ?->increment('stock', (int) $existingItem->quantity);
                }
            }

            $sale->items()->delete();

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $unitPrice = $this->resolveSaleItemPrice($item['item_type'], (int) $item['item_id']);
                $subtotal = (int) $item['quantity'] * $unitPrice;
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
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update([
                'sale_date' => $validated['sale_date'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('unit-usaha.pos', ['filter_date' => $validated['sale_date'], 'show_history' => 1])
            ->with('success', 'Transaksi POS draft berhasil diperbarui.');
    }

    public function postDailySales(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sale_date' => ['required', 'date'],
        ]);

        $saleDate = Carbon::parse($validated['sale_date'])->toDateString();
        $sales = UnitUsahaSale::query()
            ->whereDate('sale_date', $saleDate)
            ->where('status', 'draft')
            ->where('is_posted', false)
            ->where(function ($query) {
                $query->whereNull('source_module')
                    ->orWhere('source_module', 'unit-usaha');
            })
            ->get();

        if ($sales->isEmpty()) {
            return redirect()
                ->route('unit-usaha.pos', ['filter_date' => $saleDate, 'show_history' => 1])
                ->with('success', 'Tidak ada transaksi draft untuk diposting pada tanggal tersebut.');
        }

        DB::transaction(function () use ($sales) {
            foreach ($sales as $sale) {
                $journal = $this->journalService->createMappedJournal(
                    sourceModule: 'unit-usaha',
                    transactionType: 'retail-sale-' . $sale->category . '-' . $sale->payment_method,
                    referenceType: 'unit_usaha_sale',
                    referenceId: $sale->id,
                    amount: (float) $sale->total_amount,
                    memo: 'Transaksi POS ' . $sale->sale_number . ' - Toko',
                    entryDate: $sale->sale_date,
                );

                $sale->update([
                    'status' => 'posted',
                    'is_posted' => true,
                    'journal_entry_id' => $journal->id,
                ]);
            }
        });

        return redirect()
            ->route('unit-usaha.pos', ['filter_date' => $saleDate, 'show_history' => 1])
            ->with('success', 'Posting harian transaksi POS berhasil dijalankan.');
    }

    public function inventory(): View
    {
        $inventories = UnitUsahaInventory::query()->with('categoryRelation')->orderBy('name')->get();

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
        $services = UnitUsahaService::query()->latest()->get();
        $inventories = UnitUsahaInventory::query()
            ->with('categoryRelation')
            ->whereHas('categoryRelation', function ($query) {
                $query->where('is_active', true);
            })
            ->latest()
            ->get();
        $categories = UnitUsahaInventoryCategory::query()->withCount('inventories')->latest()->get();

        return view('unit-usaha.master-products', [
            'title' => 'MASTER UNIT USAHA - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Unit Usaha',
            'pageTitle' => 'MASTER UNIT USAHA',
            'pageDescription' => 'Pisahkan pengelolaan jasa dan barang agar data master unit usaha lebih rapi, konsisten, dan mudah dipakai di operasional harian.',
            'headerTabs' => $this->masterTabs('unit-usaha.products'),
            'inventoryCategories' => $categories->where('is_active', true)->where('usage_type', 'barang')->sortBy('code')->values(),
            'serviceCategories' => $categories->where('is_active', true)->where('usage_type', 'jasa')->sortBy('code')->values(),
            'inventoryItems' => $inventories->sortBy('name')->values(),
            'serviceItems' => $services->sortBy('name')->values(),
            'recentPriceHistories' => UnitUsahaInventoryPriceHistory::query()
                ->with('inventory')
                ->latest('effective_at')
                ->latest('id')
                ->get(),
            'servicesCount' => $services->count(),
            'activeServicesCount' => $services->where('is_active', true)->count(),
            'serviceCategoriesCount' => $categories->where('usage_type', 'jasa')->where('is_active', true)->count(),
            'inventoriesCount' => $inventories->count(),
            'activeInventoriesCount' => $inventories->where('is_active', true)->count(),
            'inventoryCategoriesCount' => $categories->where('usage_type', 'barang')->where('is_active', true)->count(),
            'lowStockCount' => $inventories->filter(fn ($item) => $item->stock <= $item->minimum_stock)->count(),
            'usedInventoryCategoriesCount' => $categories->where('inventories_count', '>', 0)->count(),
        ]);
    }

    public function masterServices(): View
    {
        return view('unit-usaha.master-services', [
            'title' => 'JASA/SERVICE - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Jasa Unit Usaha',
            'pageTitle' => 'JASA/SERVICE',
            'pageDescription' => 'Kelola layanan jasa unit usaha seperti fotokopi, print, jilid, laminating, dan layanan operasional lain.',
            'headerTabs' => $this->masterTabs('unit-usaha.master.services'),
            'services' => UnitUsahaService::query()->latest()->get(),
            'serviceCategories' => UnitUsahaInventoryCategory::query()->where('is_active', true)->where('usage_type', 'jasa')->orderBy('code')->get(),
        ]);
    }

    public function storeMasterService(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:unit_usaha_services,code'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:unit_usaha_inventory_categories,id'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = UnitUsahaInventoryCategory::query()
            ->where('usage_type', 'jasa')
            ->findOrFail($validated['category_id']);

        UnitUsahaService::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'category' => $category->code,
            'unit' => $validated['unit'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Jasa/service berhasil ditambahkan.')
            ->with('master_tab', 'jasa');
    }

    public function masterInventory(): View
    {
        return view('unit-usaha.master-inventory', [
            'title' => 'INVENTORY/ATK - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Barang Unit Usaha',
            'pageTitle' => 'INVENTORY/ATK',
            'pageDescription' => 'Kelola master barang inventory, ATK, bahan baku, dan item fisik yang dipantau stoknya di unit usaha.',
            'headerTabs' => $this->masterTabs('unit-usaha.master.inventory'),
            'inventories' => UnitUsahaInventory::query()->with('categoryRelation')->latest()->get(),
            'inventoryCategories' => UnitUsahaInventoryCategory::query()->where('is_active', true)->where('usage_type', 'barang')->orderBy('code')->get(),
        ]);
    }

    public function storeMasterInventory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:unit_usaha_inventories,code'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:unit_usaha_inventory_categories,id'],
            'unit' => ['required', 'string', 'max:50'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = UnitUsahaInventoryCategory::query()->findOrFail($validated['category_id']);

        $inventory = UnitUsahaInventory::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => $validated['name'],
            'category_id' => $category->id,
            'category' => $category->code,
            'unit' => $validated['unit'],
            'stock' => 0,
            'minimum_stock' => $validated['minimum_stock'],
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $this->recordInventoryPriceHistory(
            inventory: $inventory,
            priceType: 'purchase',
            oldPrice: null,
            newPrice: (float) $inventory->purchase_price,
            changeSource: 'master_create',
            notes: 'Harga beli awal saat master barang dibuat.'
        );

        $this->recordInventoryPriceHistory(
            inventory: $inventory,
            priceType: 'selling',
            oldPrice: null,
            newPrice: (float) $inventory->selling_price,
            changeSource: 'master_create',
            notes: 'Harga jual awal saat master barang dibuat.'
        );

        return redirect()
            ->back()
            ->with('success', 'Inventory/ATK berhasil ditambahkan.')
            ->with('master_tab', 'barang');
    }

    public function updateInventoryPrices(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'master_tab' => ['nullable', 'string'],
            'price_inventory_id' => ['required', 'exists:unit_usaha_inventories,id'],
            'new_purchase_price' => ['required', 'numeric', 'min:0'],
            'new_selling_price' => ['required', 'numeric', 'min:0'],
            'change_notes' => ['nullable', 'string'],
        ]);

        $inventory = UnitUsahaInventory::query()->findOrFail($validated['price_inventory_id']);
        $oldPurchasePrice = (float) $inventory->purchase_price;
        $oldSellingPrice = (float) $inventory->selling_price;
        $newPurchasePrice = (float) $validated['new_purchase_price'];
        $newSellingPrice = (float) $validated['new_selling_price'];

        if ($oldPurchasePrice === $newPurchasePrice && $oldSellingPrice === $newSellingPrice) {
            return back()
                ->withErrors(['price_inventory_id' => 'Tidak ada perubahan harga yang disimpan.'])
                ->withInput();
        }

        $inventory->update([
            'purchase_price' => $newPurchasePrice,
            'selling_price' => $newSellingPrice,
        ]);

        if ($oldPurchasePrice !== $newPurchasePrice) {
            $this->recordInventoryPriceHistory(
                inventory: $inventory,
                priceType: 'purchase',
                oldPrice: $oldPurchasePrice,
                newPrice: $newPurchasePrice,
                changeSource: 'manual_update',
                notes: $validated['change_notes'] ?: 'Perubahan manual harga beli dari master barang.'
            );
        }

        if ($oldSellingPrice !== $newSellingPrice) {
            $this->recordInventoryPriceHistory(
                inventory: $inventory,
                priceType: 'selling',
                oldPrice: $oldSellingPrice,
                newPrice: $newSellingPrice,
                changeSource: 'manual_update',
                notes: $validated['change_notes'] ?: 'Perubahan manual harga jual dari master barang.'
            );
        }

        $this->retailInventoryBridgeService->syncRetailItemsFromInventory($inventory->fresh());

        return back()->with('success', 'Perubahan harga barang berhasil disimpan ke histori harga.');
    }

    public function masterInventoryCategories(): View
    {
        $categories = UnitUsahaInventoryCategory::query()
            ->withCount('inventories')
            ->latest()
            ->get();

        return view('unit-usaha.master-inventory-categories', [
            'title' => 'JENIS BARANG - Koperasi Digital Mandiri',
            'sectionLabel' => 'Master Barang Unit Usaha',
            'pageTitle' => 'JENIS BARANG',
            'pageDescription' => 'Kelola master jenis barang agar inventory, pembelian, dan stock opname memakai klasifikasi yang konsisten.',
            'headerTabs' => $this->masterTabs('unit-usaha.master.inventory-categories'),
            'categories' => $categories,
        ]);
    }

    public function storeMasterInventoryCategory(Request $request): RedirectResponse
    {
        if ($request->filled('category_id')) {
            $validated = $request->validate([
                'category_id' => ['required', 'exists:unit_usaha_inventory_categories,id'],
                'description' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $category = UnitUsahaInventoryCategory::query()->findOrFail($validated['category_id']);
            $category->update([
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            return redirect()
                ->route('unit-usaha.master.inventory-categories')
                ->with('success', 'Jenis barang berhasil diperbarui.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:unit_usaha_inventory_categories,code'],
            'name' => ['required', 'string', 'max:100', 'unique:unit_usaha_inventory_categories,name'],
            'usage_type' => ['required', 'in:barang,jasa'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        UnitUsahaInventoryCategory::create([
            'code' => strtoupper(trim((string) $validated['code'])),
            'name' => trim((string) $validated['name']),
            'usage_type' => $validated['usage_type'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()
            ->route('unit-usaha.master.inventory-categories')
            ->with('success', 'Jenis barang berhasil ditambahkan.');
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
                $oldPurchasePrice = (float) $inventory->purchase_price;
                $newPurchasePrice = (float) $item['unit_price'];
                $inventory->update([
                    'purchase_price' => $newPurchasePrice,
                ]);

                if ($oldPurchasePrice !== $newPurchasePrice) {
                    $this->recordInventoryPriceHistory(
                        inventory: $inventory,
                        priceType: 'purchase',
                        oldPrice: $oldPurchasePrice,
                        newPrice: $newPurchasePrice,
                        changeSource: 'purchase',
                        referenceType: UnitUsahaPurchase::class,
                        referenceId: $purchase->id,
                        notes: 'Update dari transaksi pembelian ' . $purchase->purchase_number
                    );
                }
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
                'route' => 'unit-usaha.products',
                'label' => 'RINGKASAN MASTER',
                'icon' => 'fas fa-table-cells-large',
                'summary' => 'Pintu masuk master unit usaha dengan pemisahan jelas antara jasa dan barang.',
            ],
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
            [
                'route' => 'unit-usaha.master.inventory-categories',
                'label' => 'JENIS BARANG',
                'icon' => 'fas fa-layer-group',
                'summary' => 'Klasifikasi barang untuk master inventory, pembelian, dan stock opname.',
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

    private function resolveSaleItemPrice(string $itemType, int $itemId): float
    {
        if ($itemType === 'service') {
            return (float) UnitUsahaService::query()->findOrFail($itemId)->price;
        }

        return (float) UnitUsahaInventory::query()->findOrFail($itemId)->selling_price;
    }

    private function recordInventoryPriceHistory(
        UnitUsahaInventory $inventory,
        string $priceType,
        ?float $oldPrice,
        float $newPrice,
        string $changeSource,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null
    ): void {
        UnitUsahaInventoryPriceHistory::create([
            'inventory_id' => $inventory->id,
            'price_type' => $priceType,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_source' => $changeSource,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'changed_by' => auth()->id(),
            'effective_at' => now(),
        ]);
    }

    private function saleCanBeEdited(UnitUsahaSale $sale): bool
    {
        $isManualPos = in_array($sale->source_module, [null, 'unit-usaha'], true)
            && $sale->source_reference_type === null
            && $sale->source_reference_id === null;

        return $isManualPos
            && ! $sale->is_posted
            && $sale->status !== 'posted'
            && optional($sale->sale_date)->isToday();
    }
}
