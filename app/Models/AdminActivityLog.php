<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'timestamp',
        'name',
        'role',
        'activity',
        'details',
        'device',
    ];

    public $timestamps = false;
}
