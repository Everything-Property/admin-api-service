<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyVideoLink extends Model
{
    use SoftDeletes; // Since you have deleted_at column

    protected $table = 'property_video_link';

    protected $fillable = [
        'property_id',
        'link',
        'type'
    ];

    // Custom timestamp column name for updated_at
    const UPDATED_AT = 'modified_at';

    // Relationship with Property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Define which fields should be treated as dates
    protected $dates = [
        'created_at',
        'modified_at',
        'deleted_at'
    ];
} 