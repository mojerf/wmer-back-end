<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    /** @use HasFactory<\Database\Factories\WorkFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image',
        'title',
        'slug',
        'timeline',
        'publish_date',
        'role',
        'tags',
        'project_link',
        'full_image',
        'overview',
        'learn',
        'description',
    ];
}
