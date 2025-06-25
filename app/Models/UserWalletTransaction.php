<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWalletTransaction extends Model
{
    protected $table = 'user_wallet_transaction';

    protected $fillable = [
        'user_wallet_id',
        'amount',
        'type',
        'status',
        'narration',
    ];

    public function wallet()
    {
        return $this->belongsTo(UserWallet::class, 'user_wallet_id');
    }
}
