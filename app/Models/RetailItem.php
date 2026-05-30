<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetailItem extends Model
{
    protected $table = 'retail_items';

    protected $fillable = [
        'sku',
        'name',
        'unit',
        'price',
        'category',
        'stock',
        'description',
        'is_active',
        'linked_inventory_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactionItems(): HasMany
    {
        return $this->hasMany(RetailTransactionItem::class);
    }

    public function linkedInventory(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventory::class, 'linked_inventory_id');
    }
}
