<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $authorName = trim($this->user->first_name.' '.$this->user->last_name);

        return [
            'id' => $this->id,
            // Never expose the commenter's e-mail address publicly.
            'author_name' => $authorName ?: 'ناشناس',
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'created_at' => $this->created_at->toIso8601String(),
            'can_delete' => (bool) $request->user()?->can('delete', $this->resource),
            'replies' => self::collection($this->whenLoaded('replies')),
        ];
    }
}
