<?php

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string|null $image_thumb Small WebP cover for list pages (null for older content)
 */
class Product extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'user_id',
        'image',
        'image_thumb',
        'title',
        'slug',
        'price',
        'price_with_discount',
        'expert',
        'description',
        'download_link',
    ];

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderByDesc('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product')->withPivot('op_price');
    }
}
