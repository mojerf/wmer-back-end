<?php

namespace App\Http\Resources\post;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostAllResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => asset('storage/'.$this->image),
            'created_at' => $this->created_at->toIso8601String(),
            'comments_count' => $this->comments_count,
        ];
    }
}
