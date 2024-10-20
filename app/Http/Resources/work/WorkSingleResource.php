<?php

namespace App\Http\Resources\work;

use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkSingleResource extends JsonResource
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
            "timeline" => $this->timeline,
            "publish_date" => $this->publish_date,
            "role" => $this->role,
            "tags" => $this->tags,
            "project_link" => $this->project_link,
            "full_image" => $this->full_image,
            "overview" => $this->overview,
            "learn" => $this->learn,
            "description" => $this->description,
            'tags' => explode(',', $this->tags),
            'comments' => CommentResource::collection($this->comments),
        ];
    }
}
