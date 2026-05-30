<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitUsahaPurchase extends Model
{
    protected $table = 'unit_usaha_purchases';

    protected $fillable = [
        'purchase_number',
        'purchase_date',
        'supplier_name',
        'notes',
        'total_amount',
        'payment_method',
        'status',
        'is_posted',
        'journal_entry_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(UnitUsahaPurchaseItem::class, 'purchase_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
