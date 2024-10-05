<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyMedia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'property_media';

    protected $fillable = [
        'property_id',
        'original_file_name',
        'file_name',
        'fingerprint',
        'token',
        'primary_image',
        'rotate_degree',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';
    const DELETED_AT = 'deleted_at';

    // Define relationships
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
