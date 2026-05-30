<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $fillable = [
        'nik',
        'name',
        'email',
        'login',
        'spouse_name',
        'npwp',
        'phone',
        'address',
        'company_unit',
        'account_number',
        'status',
        'membership_type',
        'balance_receivable',
        'balance_payable',
    ];

    protected $casts = [
        'balance_receivable' => 'decimal:2',
        'balance_payable' => 'decimal:2',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function savings(): HasMany
    {
        return $this->hasMany(Saving::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function retailTransactions(): HasMany
    {
        return $this->hasMany(RetailTransaction::class);
    }

    public function memberLedgers(): HasMany
    {
        return $this->hasMany(MemberLedger::class);
    }

    public function receivablePayments(): HasMany
    {
        return $this->hasMany(MemberReceivablePayment::class);
    }
}
