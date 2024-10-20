<?php

namespace App\Http\Resources\post;

use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostSingleResource extends JsonResource
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
            'author' => $this->user->first_name . ' ' . $this->user->last_name,
            'description' => $this->description,
            'created_at' => $this->created_at->diffForHumans(),
            'comments_count' => $this->comments->count(),
            'comments' => CommentResource::collection($this->comments),
        ];
    }
}
