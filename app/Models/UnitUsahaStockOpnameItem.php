<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitUsahaStockOpnameItem extends Model
{
    protected $table = 'unit_usaha_stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'inventory_id',
        'system_stock',
        'physical_stock',
        'difference',
        'notes',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaStockOpname::class, 'stock_opname_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaInventory::class, 'inventory_id');
    }
}
