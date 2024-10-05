<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'subscription_plan'; // Specify the table name

    protected $fillable = [
        'title',
        'price',
        'yearly_discount',
        'role',
        'recommended',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

    // Define relationships
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscriptionPlan::class, 'subscription_plan_id');
    }
}
