<?php

namespace App\Http\Resources\post;

use App\Support\ImageStorage;
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
            'image' => ImageStorage::url($this->image),
            // Small WebP for cards and lists (falls back to the full image for older content).
            'image_thumb' => ImageStorage::thumbUrl($this->image_thumb, $this->image),
            'created_at' => $this->created_at->toIso8601String(),
            'comments_count' => $this->comments_count,
        ];
    }
}
