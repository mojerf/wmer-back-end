<?php

namespace App\Http\Resources;

use App\Http\Resources\product\ProductAllResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $َbuyer_name = $this->user->first_name . ' ' . $this->user->last_name;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'buyer_name' => trim($َbuyer_name) ?: 'ناشناس',
            'products' => ProductOrderResource::collection($this->products),
            'total_price' => $this->total_price,
            'status' => $this->status,
            'date' => $this->created_at->diffForHumans(),
        ];
    }
}
