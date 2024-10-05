<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'user';


    protected $fillable = [
        'username',
        'roles',
        'password',
        'first_name',
        'last_name',
        'phone',
        'email',
        'user_verified',
        'email_verified',
        'phone_verified',
        'country_id',
        'profile_picture',
        'kyc_verified',
        'banner_image',
        'parent_user_id',
        'permissions',
    ];

    protected $casts = [
        'roles' => 'array',
        'email_verified' => 'datetime',
        'phone_verified' => 'datetime',
        'created_at' => 'datetime',
        'modified_at' => 'datetime',
        'kyc_verified' => 'boolean',
        'user_verified' => 'boolean',
    ];

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function bankAccount()
    {
        return $this->hasOne(UserBankAccountDetail::class);
    }

    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class);
    }

    // Define the relationship to user subscriptions
    public function userSubscriptions()
    {
        return $this->hasMany(UserSubscriptionPlan::class, 'user_id');
    }
}
