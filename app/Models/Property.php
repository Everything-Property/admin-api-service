<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $table = 'property';

    protected $fillable = [
        'type_id',
        'category_id',
        'title',
        'slug',
        'price',
        'keywords',
        'address',
        'longitude',
        'latitude',
        'bedroom',
        'bathroom',
        'garage',
        'description',
        'area',
        'user_id',
        'state_locality_id',
        'inspection_fee',
        'project_id',
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

}
