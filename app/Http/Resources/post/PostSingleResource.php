<?php

namespace App\Http\Resources\post;

use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostSingleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => ImageStorage::url($this->image),
            'author' => trim($this->user->first_name.' '.$this->user->last_name) ?: null,
            'description' => $this->description,
            'comments_count' => $this->comments_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
