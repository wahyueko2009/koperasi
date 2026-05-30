<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitUsahaStockOpname extends Model
{
    protected $table = 'unit_usaha_stock_opnames';

    protected $fillable = [
        'opname_number',
        'opname_date',
        'notes',
        'status',
        'adjustment_value',
        'is_posted',
        'journal_entry_id',
    ];

    protected $casts = [
        'opname_date' => 'date',
        'adjustment_value' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(UnitUsahaStockOpnameItem::class, 'stock_opname_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
