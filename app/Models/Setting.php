<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'logo', 'password', 'push_notification',
        'email_notification', 'sms_notification', 'privacy', 'dark_mode',
        'language', 'two_factor_auth', 'facebook', 'instagram', 'linkedin', 'twitter',
    ];
}
