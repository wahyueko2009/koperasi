<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlCostCenter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'pic_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function journals(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'cost_center_id');
    }
}
