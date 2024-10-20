<?php

namespace App\Http\Resources\product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAllResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image,
            'price' => $this->price_with_discount ?? $this->price,
            'has_discount' => $this->price_with_discount ? true : false,
            'created_at' => $this->created_at->diffForHumans(),
            'comments_count' => $this->comments->count(),
        ];
    }
}
