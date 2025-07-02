<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateLocality extends Model
{
    use HasFactory;

    protected $table = 'state_locality';

    protected $fillable = [
        'name',
        'state_id',
        'active',
        'sorting',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the state that owns this locality
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get property requests for this locality
     */
    public function propertyRequests()
    {
        return $this->hasMany(PropertyRequest::class, 'state_locality_id');
    }

    /**
     * Scope for active localities
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
