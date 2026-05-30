<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitUsahaPurchaseItem extends Model
{
    protected $table = 'unit_usaha_purchase_items';

    protected $fillable = [
        'purchase_id',
        'inventory_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaPurchase::class, 'purchase_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventory::class, 'inventory_id');
    }
}
