<?php

namespace App\Http\Resources\product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAllResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => asset('storage/'.$this->image),
            // `price` is what the customer pays; `old_price` is only present for discounted products.
            'price' => $this->price_with_discount ?? $this->price,
            'old_price' => $this->price_with_discount ? $this->price : null,
            'has_discount' => $this->price_with_discount !== null,
            'created_at' => $this->created_at->toIso8601String(),
            'comments_count' => $this->comments_count,
        ];
    }
}
