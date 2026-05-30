<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetMutation extends Model
{
    protected $table = 'fixed_asset_mutations';

    protected $fillable = [
        'fixed_asset_id',
        'mutation_date',
        'mutation_type',
        'from_location',
        'to_location',
        'from_status',
        'to_status',
        'disposal_value',
        'disposal_debit_account_id',
        'disposal_gain_account_id',
        'disposal_loss_account_id',
        'disposal_reference_number',
        'notes',
    ];

    protected $casts = [
        'mutation_date' => 'date',
        'disposal_value' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function disposalDebitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'disposal_debit_account_id');
    }

    public function disposalGainAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'disposal_gain_account_id');
    }

    public function disposalLossAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'disposal_loss_account_id');
    }
}
