<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitUsahaInventory extends Model
{
    protected $table = 'unit_usaha_inventories';

    protected $fillable = [
        'code',
        'name',
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
}
