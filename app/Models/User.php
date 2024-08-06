<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    protected function casts(): array
    {
        return [
            'email_verified' => 'datetime',
            'phone_verified' => 'datetime',
            'created_at' => 'datetime',
            'modified_at' => 'datetime',
            'kyc_verified' => 'boolean',
            'user_verified' => 'boolean',
        ];
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function socialMedia()
    {
        return $this->hasOne(SocialMedia::class);
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class);
    }

    public function wallet()
    {
        return $this->hasOne(UserWallet::class, 'user_id');
    }
}
