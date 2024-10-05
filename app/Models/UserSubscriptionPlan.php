<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'user_subscription_plan'; // Specify the table name

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'start_at',
        'end_at',
        'auto_renew',
    ];

    // Define relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define relationship to SubscriptionPlan
    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
