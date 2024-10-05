<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyCategory extends Model
{
    use HasFactory;

    protected $table = 'property_category';

    protected $fillable = [
        'title',
        'active',
        'slug',
        'parent_id',
        'sold_by_area',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

    // Define relationships here
    public function parentCategory()
    {
        return $this->belongsTo(PropertyCategory::class, 'parent_id');
    }

    public function subCategories()
    {
        return $this->hasMany(PropertyCategory::class, 'parent_id');
    }
}
