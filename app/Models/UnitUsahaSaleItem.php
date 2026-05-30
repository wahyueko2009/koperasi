<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitUsahaSaleItem extends Model
{
    protected $table = 'unit_usaha_sale_items';

    protected $fillable = [
        'sale_id',
        'item_type',
        'service_id',
        'inventory_id',
        'retail_item_id',
        'item_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaSale::class, 'sale_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaService::class, 'service_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventory::class, 'inventory_id');
    }

    public function retailItem(): BelongsTo
    {
        return $this->belongsTo(RetailItem::class, 'retail_item_id');
    }
}
