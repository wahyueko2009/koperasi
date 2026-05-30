<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitUsahaSale extends Model
{
    protected $table = 'unit_usaha_sales';

    protected $fillable = [
        'sale_number',
        'sale_date',
        'member_id',
        'category',
        'payment_method',
        'notes',
        'total_amount',
        'status',
        'is_posted',
        'journal_entry_id',
        'source_module',
        'source_reference_type',
        'source_reference_id',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnitUsahaSaleItem::class, 'sale_id');
    }
}
