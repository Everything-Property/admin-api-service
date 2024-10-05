<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProperties extends Model
{
    use HasFactory;

    protected $table = 'project_properties';

    protected $fillable = [
        'project_id',
        'bedrooms',
        'bathrooms',
        'plot_size',
        'amenities',
        'description',
        'image',
        'house_type',
        'totalUnitAvailable',
        'price',
        'total_unit_available',
        'project_properties_id',
    ];

    public $timestamps = true;

    // Define relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
