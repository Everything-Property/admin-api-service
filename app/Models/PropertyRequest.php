<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyRequest extends Model
{
    use HasFactory;

    protected $table = 'property_request';

    protected $fillable = [
        'property_type_id',
        'property_category_id',
        'state_id',
        'state_locality_id',
        'full_name',
        'user_type',
        'email',
        'phone',
        'additional_information',
        'active',
        'bedroom',
        'bathroom',
        'user_id',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'last_notified_at';

    // Define relationships
    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'property_category_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function stateLocality()
    {
        return $this->belongsTo(StateLocality::class, 'state_locality_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function viewingRequests()
    {
        return $this->hasMany(UserViewingRequest::class, 'property_request_id');
    }
}
