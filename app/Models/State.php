<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $table = 'state';

    protected $fillable = [
        'country_id',
        'name',
        'sorting',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the localities for this state
     */
    public function localities()
    {
        return $this->hasMany(StateLocality::class);
    }

    /**
     * Get property requests for this state
     */
    public function propertyRequests()
    {
        return $this->hasMany(PropertyRequest::class);
    }

    /**
     * Scope for active states
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
