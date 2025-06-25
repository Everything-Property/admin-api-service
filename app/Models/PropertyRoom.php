<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRoom extends Model
{
    protected $table = 'property_room';

    protected $fillable = [
        'property_id',
        'title',
        'area',
        'description'
    ];

    // Relationship with Property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Relationship with room media if you have any
    public function roomMedia()
    {
        return $this->hasMany(RoomMedia::class);
    }
} 