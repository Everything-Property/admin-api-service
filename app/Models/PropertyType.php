<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    use HasFactory;

    protected $table = 'property_type'; // Specify the table name

    protected $fillable = [
        'title',
        'active',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

    // Define relationships
    public function properties()
    {
        return $this->hasMany(Property::class, 'type_id');
    }
}
