<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBankAccountDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_bank_account_detail';

    protected $fillable = [
        'user_id',
        'account_number',
        'bank_code',
        'bank_name',
        'account_name',
        'sub_account_id',
        'sub_account_rs_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}