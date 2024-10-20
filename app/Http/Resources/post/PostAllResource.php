<?php

namespace App\Http\Resources\post;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostAllResource extends JsonResource
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
            'created_at' => $this->created_at->diffForHumans(),
            'comments_count' => $this->comments->count(),
        ];
    }
}
