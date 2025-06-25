<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoostPlan extends Model
{
    use HasFactory;

    protected $table = 'boost_plans';

    protected $fillable = [
        'name',
        'description',
        'listing_limit',
        'base_price',
        'quarterly_discount',
        'yearly_discount',
        'free_viewing_requests_per_month',
        'is_active',
        'is_recommended',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'quarterly_discount' => 'decimal:2',
        'yearly_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
        'listing_limit' => 'integer',
        'free_viewing_requests_per_month' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the pricing for different account types
     */
    public function pricing(): HasMany
    {
        return $this->hasMany(BoostPlanPricing::class);
    }

    /**
     * Get user subscriptions for this plan
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserBoostSubscription::class);
    }

    /**
     * Get the price for a specific account type and billing cycle
     */
    public function getPriceForAccountType(string $accountType, string $billingCycle = 'monthly'): float
    {
        $pricing = $this->pricing()->where('account_type', $accountType)->first();
        
        $basePrice = $this->base_price;
        
        if ($pricing) {
            if ($pricing->custom_price) {
                $basePrice = $pricing->custom_price;
            } else {
                $basePrice = $this->base_price * $pricing->price_multiplier;
            }
        }
        
        // Apply discounts based on billing cycle
        switch ($billingCycle) {
            case 'quarterly':
                return $basePrice * 3 * (1 - $this->quarterly_discount / 100);
            case 'yearly':
                return $basePrice * 12 * (1 - $this->yearly_discount / 100);
            default:
                return $basePrice;
        }
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('base_price');
    }
}