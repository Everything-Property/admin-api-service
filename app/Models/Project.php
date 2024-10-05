<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'project'; // Specify the table name

    protected $fillable = [
        'name',
        'project_type',
        'keyword',
        'plot_size',
        'description',
        'amenities',
        'state',
        'locality',
        'address',
        'longitude',
        'latitude',
        'property_media_id',
        'user_id',
        'status',
        'location',
    ];

    public $timestamps = true;

    // Define relationships
    public function media()
    {
        return $this->hasMany(ProjectMedia::class, 'project_id');
    }

    public function properties()
    {
        return $this->hasMany(ProjectProperties::class, 'project_id');
    }
}
