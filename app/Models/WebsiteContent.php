<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WebsiteContent extends Model
{
    use HasFactory;

    protected $table = 'website_content';

    protected $fillable = [
        'content_key',
        'title',
        'content',
        'cover_image',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'modified_at';

    // Accessor to get the full URL of the cover image
    public function getCoverImageAttribute($value)
    {
        // Return the full URL to the cover image
        return $value ? asset('storage/' . $value) : null;
    }
}
