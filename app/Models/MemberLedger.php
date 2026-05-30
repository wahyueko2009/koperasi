<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLedger extends Model
{
    protected $table = 'member_ledgers';

    protected $fillable = [
        'member_id',
        'ledger_scope',
        'entry_date',
        'transaction_type',
        'transaction_id',
        'debit',
        'credit',
        'balance',
        'memo',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
