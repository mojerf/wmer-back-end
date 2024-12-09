<?php

namespace App\Http\Resources\product;

use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSingleResource extends JsonResource
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
            'image' => asset('storage/' . $this->image),
            'author' => $this->user->first_name . ' ' . $this->user->last_name,
            'price' => $this->price,
            'price_with_discount' => $this->price_with_discount,
            'expert' => $this->expert,
            'description' => $this->description,
            'created_at' => $this->created_at->diffForHumans(),
            'comments_count' => $this->comments->count(),
            'comments' => CommentResource::collection($this->comments),
        ];
    }
}
