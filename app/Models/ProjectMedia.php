<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_media'; // Specify the table name

    protected $fillable = [
        'original_file_name',
        'file_name',
        'fingerprint',
        'token',
        'primary_image',
        'rotate_degree',
        'project_id',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';
    const DELETED_AT = 'deleted_at';

    // Define relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
