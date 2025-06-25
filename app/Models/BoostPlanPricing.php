<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoostPlanPricing extends Model
{
    use HasFactory;

    protected $table = 'boost_plan_pricing';

    protected $fillable = [
        'boost_plan_id',
        'account_type',
        'monthly_price',
        'quarterly_price',
        'yearly_price',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'quarterly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

    /**
     * Get the boost plan that owns this pricing
     */
    public function boostPlan(): BelongsTo
    {
        return $this->belongsTo(BoostPlan::class);
    }

    /**
     * Get the final price for this account type and billing cycle
     */
    public function getFinalPrice(string $billingCycle = 'monthly'): float
    {
        switch ($billingCycle) {
            case 'quarterly':
                return $this->quarterly_price;
            case 'yearly':
                return $this->yearly_price;
            default:
                return $this->monthly_price;
        }
    }

    /**
     * Scope for specific account type
     */
    public function scopeForAccountType($query, string $accountType)
    {
        return $query->where('account_type', $accountType);
    }
}
