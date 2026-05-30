<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PiutangUsahaInvoiceItem extends Model
{
    protected $table = 'piutang_usaha_invoice_items';

    protected $fillable = [
        'invoice_id',
        'contract_item_id',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaInvoice::class, 'invoice_id');
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaContractItem::class, 'contract_item_id');
    }
}
