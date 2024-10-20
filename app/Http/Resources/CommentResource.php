<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $َauthor_name = $this->user->first_name . ' ' . $this->user->last_name;

        return [
            'id' => $this->id,
            'author_name' => trim($َauthor_name) ?: 'ناشناس',
            'author_email' => $this->user->email,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'date' => $this->created_at->diffForHumans(),
        ];
    }
}
