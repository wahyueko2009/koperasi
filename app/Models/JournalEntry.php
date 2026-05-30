<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'entry_date',
        'period_id',
        'cost_center_id',
        'reference_type',
        'reference_id',
        'source_module',
        'reference_number',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'memo',
        'status',
        'posted_at',
        'created_by',
        'posted_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(GlPeriod::class, 'period_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(GlCostCenter::class, 'cost_center_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_id');
    }

    public function generalLedgerEntries(): HasMany
    {
        return $this->hasMany(GeneralLedger::class);
    }
}
