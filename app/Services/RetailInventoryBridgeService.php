<?php

namespace App\Services;

use App\Models\RetailItem;
use App\Models\UnitUsahaInventory;

class RetailInventoryBridgeService
{
    public function syncAllRetailItemsToInventories(): void
    {
        RetailItem::query()->get()->each(function (RetailItem $item): void {
            $this->syncInventoryFromRetailItem($item);
        });
    }

    public function syncInventoryFromRetailItem(RetailItem $retailItem): UnitUsahaInventory
    {
        $inventory = $this->resolveInventory($retailItem);

        $inventory->update([
            'name' => $retailItem->name,
            'category' => strtoupper((string) $retailItem->category),
            'unit' => $retailItem->unit,
            'stock' => (int) $retailItem->stock,
            'selling_price' => (float) $retailItem->price,
            'purchase_price' => (float) ($inventory->purchase_price ?: $retailItem->price),
            'description' => $retailItem->description,
            'is_active' => (bool) $retailItem->is_active,
        ]);

        return $inventory->fresh();
    }

    public function syncRetailItemsFromInventory(UnitUsahaInventory $inventory): void
    {
        RetailItem::query()
            ->where('linked_inventory_id', $inventory->id)
            ->get()
            ->each(function (RetailItem $item) use ($inventory): void {
                $item->update([
                    'name' => $inventory->name,
                    'unit' => $inventory->unit,
                    'category' => strtolower((string) $inventory->category),
                    'stock' => (int) $inventory->stock,
                    'price' => (float) $inventory->selling_price,
                    'description' => $inventory->description,
                    'is_active' => (bool) $inventory->is_active,
                ]);
            });
    }

    private function resolveInventory(RetailItem $retailItem): UnitUsahaInventory
    {
        if ($retailItem->linked_inventory_id) {
            $inventory = UnitUsahaInventory::query()->find($retailItem->linked_inventory_id);
            if ($inventory) {
                return $inventory;
            }
        }

        $inventory = UnitUsahaInventory::query()->firstOrCreate(
            ['code' => $this->inventoryCode($retailItem)],
            [
                'name' => $retailItem->name,
                'category' => strtoupper((string) $retailItem->category),
                'unit' => $retailItem->unit,
                'stock' => (int) $retailItem->stock,
                'minimum_stock' => 0,
                'purchase_price' => (float) $retailItem->price,
                'selling_price' => (float) $retailItem->price,
                'description' => $retailItem->description,
                'is_active' => (bool) $retailItem->is_active,
            ]
        );

        $retailItem->update([
            'linked_inventory_id' => $inventory->id,
        ]);

        return $inventory;
    }

    private function inventoryCode(RetailItem $retailItem): string
    {
        $sku = strtoupper(trim((string) $retailItem->sku));

        return 'RTL-' . ($sku !== '' ? $sku : str_pad((string) $retailItem->id, 6, '0', STR_PAD_LEFT));
    }
}
