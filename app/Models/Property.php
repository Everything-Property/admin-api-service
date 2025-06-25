<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $table = 'property';

    protected $fillable = [
        'agent_id',
        'title',
        'slug',
        'type_id',
        'category_id',
        'keywords',
        'address',
        'longitude',
        'latitude',
        'bedroom',
        'bathroom',
        'garage',
        'description',
        'amenities',
        'area',
        'price',
        'is_active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Property.php

    public function type()
    {
        return $this->belongsTo(PropertyType::class, 'type_id');
    }

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'category_id');
    }

    public function propertyMedia()
    {
        return $this->hasMany(PropertyMedia::class);
    }

    public function propertyRooms()
    {
        return $this->hasMany(PropertyRoom::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'user_id', 'user_id');
    }

    public function propertyVideoLinks()
    {
        return $this->hasMany(PropertyVideoLink::class);
    }

    //remove the updated_at column from the property model
    public $timestamps = false;

}

