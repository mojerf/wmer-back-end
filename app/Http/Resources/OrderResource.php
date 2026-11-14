<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $buyerName = trim($this->user->first_name.' '.$this->user->last_name);
        $canDownload = $this->status->grantsDownloads();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'buyer_name' => $buyerName ?: 'ناشناس',
            'products' => $this->products->map(fn ($product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'image' => asset('storage/'.$product->image),
                'price' => $product->pivot->op_price,
                // Digital goods are only handed out once the order is completed.
                'download_link' => $canDownload ? $product->download_link : null,
            ])->all(),
            'total_price' => $this->total_price,
            'status' => $this->status->value,
            'date' => $this->created_at->diffForHumans(),
        ];
    }
}
