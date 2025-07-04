<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInformation extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $table = 'user_information';



    protected $fillable = [
        'user_id',
        'address',
        'website',
        'biography',
        'facebook',
        'instagram',
        'whatsapp',
        'company_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
