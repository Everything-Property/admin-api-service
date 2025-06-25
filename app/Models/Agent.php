<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $table = 'agent_information';

    protected $fillable = [
        'user_id',
        'identification_code'
    ];

    // Relationship with User model to get agent details
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with properties
    public function properties()
    {
        return $this->hasMany(Property::class, 'agent_id');
    }
} 