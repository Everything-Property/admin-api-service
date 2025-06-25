<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserViewingRequest extends Model
{
    use HasFactory;

    protected $table = 'user_viewing_requests';

    protected $fillable = [
        'user_id',
        'property_id',
        'request_type',
        'amount_charged',
        'year',
        'month',
        'status',
        'requested_at',
        'scheduled_at',
        'notes',
    ];

    protected $casts = [
        'amount_charged' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
        'requested_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Get the user that made this request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the property for this viewing request
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Check if this is a free viewing request
     */
    public function isFree(): bool
    {
        return $this->request_type === 'free';
    }

    /**
     * Check if this is a paid viewing request
     */
    public function isPaid(): bool
    {
        return $this->request_type === 'paid';
    }

    /**
     * Get the count of free viewing requests for a user in a specific month
     */
    public static function getFreeRequestsCountForMonth(int $userId, int $year, int $month): int
    {
        return static::where('user_id', $userId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('request_type', 'free')
                    ->count();
    }

    /**
     * Check if user has exceeded free viewing requests for the month
     */
    public static function hasExceededFreeQuota(int $userId, int $year, int $month, int $allowedFreeRequests): bool
    {
        $currentCount = static::getFreeRequestsCountForMonth($userId, $year, $month);
        return $currentCount >= $allowedFreeRequests;
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
     * Scope for free requests
     */
    public function scopeFree($query)
    {
        return $query->where('request_type', 'free');
    }

    /**
     * Scope for paid requests
     */
    public function scopePaid($query)
    {
        return $query->where('request_type', 'paid');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}