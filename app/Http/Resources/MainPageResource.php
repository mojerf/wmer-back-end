<?php

namespace App\Http\Resources;

use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\work\WorkAllResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainPageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'works' => WorkAllResource::collection($this->resource->works),
            'posts' => PostAllResource::collection($this->resource->posts),
            'products' => ProductAllResource::collection($this->resource->products),
        ];
    }
}
