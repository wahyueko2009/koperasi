<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiutangUsahaContractItem extends Model
{
    protected $table = 'piutang_usaha_contract_items';

    protected $fillable = [
        'contract_id',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaContract::class, 'contract_id');
    }
}
