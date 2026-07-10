<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitUsahaInventoryPriceHistory extends Model
{
    protected $table = 'unit_usaha_inventory_price_histories';

    protected $fillable = [
        'inventory_id',
        'price_type',
        'old_price',
        'new_price',
        'change_source',
        'reference_type',
        'reference_id',
        'notes',
        'changed_by',
        'effective_at',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventory::class, 'inventory_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
