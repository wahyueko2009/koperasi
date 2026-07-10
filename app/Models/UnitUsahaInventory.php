<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitUsahaInventory extends Model
{
    protected $table = 'unit_usaha_inventories';

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'category',
        'unit',
        'stock',
        'minimum_stock',
        'purchase_price',
        'selling_price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventoryCategory::class, 'category_id');
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(UnitUsahaInventoryPriceHistory::class, 'inventory_id');
    }

    public function categoryLabel(): string
    {
        return $this->categoryRelation?->name ?: (string) $this->category;
    }
}
