<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PiutangUsahaContract extends Model
{
    protected $table = 'piutang_usaha_contracts';

    protected $fillable = [
        'contract_number',
        'company_id',
        'contract_date',
        'start_date',
        'end_date',
        'service_category',
        'billing_cycle',
        'payment_term_days',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_term_days' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaCompany::class, 'company_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PiutangUsahaContractItem::class, 'contract_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PiutangUsahaInvoice::class, 'contract_id');
    }
}
