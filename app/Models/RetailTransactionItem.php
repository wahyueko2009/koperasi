<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailTransactionItem extends Model
{
    protected $table = 'retail_transaction_items';

    protected $fillable = [
        'retail_transaction_id',
        'retail_item_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(RetailTransaction::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(RetailItem::class);
    }
}
