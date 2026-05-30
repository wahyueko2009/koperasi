<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PiutangUsahaCompany extends Model
{
    protected $table = 'piutang_usaha_companies';

    protected $fillable = [
        'code',
        'name',
        'pic_name',
        'billing_email',
        'phone',
        'address',
        'npwp',
        'payment_term_days',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'payment_term_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(PiutangUsahaContract::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PiutangUsahaInvoice::class, 'company_id');
    }
}
