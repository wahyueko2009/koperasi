<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetailTransaction extends Model
{
    protected $table = 'retail_transactions';

    protected $fillable = [
        'member_id',
        'category',
        'transaction_date',
        'total_amount',
        'payment_method',
        'status',
        'notes',
        'is_posted',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RetailTransactionItem::class);
    }
}
