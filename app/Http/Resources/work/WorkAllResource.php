<?php

namespace App\Http\Resources\work;

use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkAllResource extends JsonResource
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
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $this->tags)))),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
