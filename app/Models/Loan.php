<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'application_number',
        'member_id',
        'loan_type',
        'submission_date',
        'principal_amount',
        'interest_rate',
        'tenor_months',
        'repayment_method',
        'purpose',
        'applicant_name',
        'applicant_nik',
        'company_unit',
        'applicant_phone',
        'ktp_address',
        'current_address',
        'payroll_account_number',
        'family_member_name',
        'family_relationship',
        'child_number',
        'education_level',
        'school_name',
        'school_address',
        'school_phone',
        'hospital_name',
        'hospital_address',
        'hospital_phone',
        'collateral_type',
        'collateral_value',
        'collateral_description',
        'approval_date',
        'disbursement_date',
        'maturity_date',
        'remaining_balance',
        'status',
        'application_status',
        'monthly_payment',
        'notes',
        'member_notes',
        'verification_notes',
        'approval_notes',
        'verified_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'collateral_value' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'submission_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'maturity_date' => 'date',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LoanDocument::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LoanApproval::class);
    }

    public function getLoanTypeLabelAttribute(): string
    {
        return match ($this->loan_type) {
            'emergency' => 'Emergency',
            'pendidikan' => 'Pendidikan',
            'multiguna_plus' => 'Multiguna Plus',
            default => 'Serbaguna',
        };
    }

    public function getApplicationStatusLabelAttribute(): string
    {
        return match ($this->application_status) {
            'draft' => 'Draft',
            'verified' => 'Terverifikasi',
            'submitted' => 'Menunggu Bendahara',
            'treasurer_approved' => 'Menunggu Ketua Koperasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'disbursed' => 'Dicairkan',
            'completed' => 'Selesai',
            'revision_required' => 'Perlu Revisi',
            'cancelled' => 'Dibatalkan',
            default => 'Diajukan',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Proses',
            'approved' => 'Siap Dicairkan',
            'disbursed' => 'Sudah Dicairkan',
            'active' => 'Berjalan',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => ucwords(str_replace('_', ' ', (string) $this->status)),
        };
    }
}
