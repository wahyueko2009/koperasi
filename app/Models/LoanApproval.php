<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApproval extends Model
{
    protected $fillable = [
        'loan_id',
        'official_id',
        'approval_level',
        'status',
        'action',
        'notes',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }

    public function getApprovalLevelLabelAttribute(): string
    {
        return match ($this->approval_level) {
            'bendahara' => 'Bendahara',
            'ketua_koperasi' => 'Ketua Koperasi',
            default => ucwords(str_replace('_', ' ', (string) $this->approval_level)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'queued' => 'Belum Masuk Tahap',
            'skipped' => 'Tidak Dilanjutkan',
            default => 'Menunggu',
        };
    }
}
