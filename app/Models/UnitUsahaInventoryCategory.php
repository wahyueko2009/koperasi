<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitUsahaInventoryCategory extends Model
{
    protected $table = 'unit_usaha_inventory_categories';

    protected $fillable = [
        'code',
        'name',
        'usage_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(UnitUsahaInventory::class, 'category_id');
    }
}
