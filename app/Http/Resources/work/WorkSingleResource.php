<?php

namespace App\Http\Resources\work;

use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkSingleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => ImageStorage::url($this->image),
            'full_image' => ImageStorage::url($this->full_image),
            'timeline' => $this->timeline,
            'publish_date' => $this->publish_date,
            'role' => $this->role,
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $this->tags)))),
            'project_link' => $this->project_link,
            'overview' => $this->overview,
            'learn' => $this->learn,
            'description' => $this->description,
            'comments_count' => $this->comments_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
