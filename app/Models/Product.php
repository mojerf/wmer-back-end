<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image',
        'title',
        'slug',
        'price',
        'price_with_discount',
        'expert',
        'description',
        'download_link',
    ];
}
