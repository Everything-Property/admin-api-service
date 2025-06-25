<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserBoostSubscription extends Model
{
    use HasFactory;

    protected $table = 'user_boost_subscriptions';

    protected $fillable = [
        'user_id',
        'boost_plan_id',
        'billing_cycle',
        'amount_paid',
        'discount_applied',
        'start_date',
        'end_date',
        'auto_renew',
        'status',
        'flutterwave_transaction_id',
        'payment_details',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'auto_renew' => 'boolean',
        'payment_details' => 'array',
    ];

    /**
     * Get the user that owns this subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the boost plan for this subscription
     */
    public function boostPlan(): BelongsTo
    {
        return $this->belongsTo(BoostPlan::class);
    }

    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            $this->start_date <= now() &&
            $this->end_date >= now();
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Get days remaining in subscription
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * Calculate next billing date
     */
    public function getNextBillingDate(): ?Carbon
    {
        if (!$this->auto_renew || $this->status !== 'active') {
            return null;
        }

        return $this->end_date;
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for auto-renewable subscriptions
     */
    public function scopeAutoRenewable($query)
    {
        return $query->where('auto_renew', true)
            ->where('status', 'active');
    }
}
