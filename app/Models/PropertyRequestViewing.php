<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyRequestViewing extends Model
{
    use HasFactory;

    protected $table = 'property_request_viewings';

    protected $fillable = [
        'user_id',
        'property_request_id',
        'viewing_type',
        'amount_charged',
        'year',
        'month',
        'status',
        'viewed_at',
        'flutterwave_transaction_id',
        'notes',
    ];

    protected $casts = [
        'amount_charged' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the user that made this viewing
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the property request that was viewed
     */
    public function propertyRequest(): BelongsTo
    {
        return $this->belongsTo(PropertyRequest::class);
    }

    /**
     * Check if this is a free viewing
     */
    public function isFree(): bool
    {
        return $this->viewing_type === 'free';
    }

    /**
     * Check if this is a paid viewing
     */
    public function isPaid(): bool
    {
        return $this->viewing_type === 'paid';
    }

    /**
     * Get the count of free property request viewings for a user in a specific month
     */
    public static function getFreeViewingsCountForMonth(int $userId, int $year, int $month): int
    {
        return static::where('user_id', $userId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('viewing_type', 'free')
                    ->where('status', 'completed')
                    ->count();
    }

    /**
     * Check if user has exceeded free property request viewings for the month
     */
    public static function hasExceededFreeQuota(int $userId, int $year, int $month, int $allowedFreeViewings): bool
    {
        $currentCount = static::getFreeViewingsCountForMonth($userId, $year, $month);
        return $currentCount >= $allowedFreeViewings;
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific month and year
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope for free viewings
     */
    public function scopeFree($query)
    {
        return $query->where('viewing_type', 'free');
    }

    /**
     * Scope for paid viewings
     */
    public function scopePaid($query)
    {
        return $query->where('viewing_type', 'paid');
    }

    /**
     * Scope for completed viewings
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
