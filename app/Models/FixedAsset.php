<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    protected $table = 'fixed_assets';

    protected $fillable = [
        'asset_code',
        'asset_name',
        'category',
        'acquisition_date',
        'in_service_date',
        'supplier_name',
        'location',
        'condition_status',
        'status',
        'acquisition_value',
        'residual_value',
        'useful_life_months',
        'depreciation_method',
        'asset_account_id',
        'accumulated_depreciation_account_id',
        'depreciation_expense_account_id',
        'acquisition_credit_account_id',
        'cost_center_id',
        'notes',
        'last_depreciation_at',
        'disposed_at',
        'disposal_value',
        'is_active',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'in_service_date' => 'date',
        'last_depreciation_at' => 'date',
        'disposed_at' => 'date',
        'acquisition_value' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'disposal_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_expense_account_id');
    }

    public function acquisitionCreditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'acquisition_credit_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(GlCostCenter::class, 'cost_center_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciation::class, 'fixed_asset_id');
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(FixedAssetMutation::class, 'fixed_asset_id');
    }

    public function getDepreciableBaseAttribute(): float
    {
        return max(0, (float) $this->acquisition_value - (float) $this->residual_value);
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        if ((int) $this->useful_life_months <= 0) {
            return 0.0;
        }

        return round($this->depreciable_base / (int) $this->useful_life_months, 2);
    }

    public function getAccumulatedDepreciationAttribute(): float
    {
        return round((float) $this->depreciations()->sum('amount'), 2);
    }

    public function getBookValueAttribute(): float
    {
        return round(max((float) $this->residual_value, (float) $this->acquisition_value - $this->accumulated_depreciation), 2);
    }
}
