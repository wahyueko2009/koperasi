<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PiutangUsahaInvoice extends Model
{
    protected $table = 'piutang_usaha_invoices';

    protected $fillable = [
        'invoice_number',
        'contract_id',
        'company_id',
        'invoice_date',
        'period_start',
        'period_end',
        'due_date',
        'payment_term_days',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'status',
        'notes',
        'journal_entry_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'payment_term_days' => 'integer',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaCompany::class, 'company_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(PiutangUsahaContract::class, 'contract_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PiutangUsahaInvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PiutangUsahaPayment::class, 'invoice_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function isOverdue(?CarbonInterface $date = null): bool
    {
        $date ??= now();

        if ($this->status === 'paid') {
            return false;
        }

        return (float) $this->outstanding_amount > 0
            && $this->due_date !== null
            && $this->due_date->lt($date->copy()->startOfDay());
    }

    public function statusLabel(?CarbonInterface $date = null): string
    {
        $base = match ($this->status) {
            'draft' => 'Draft',
            'issued' => 'Terbit',
            'partial' => 'Sebagian',
            'paid' => 'Lunas',
            default => ucfirst((string) $this->status),
        };

        return $this->isOverdue($date) ? $base . ' / Overdue' : $base;
    }
}
