<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Support\ImageStorage;
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
                'image' => ImageStorage::url($product->image),
                'price' => $product->pivot->op_price,
                // Digital goods are only handed out once the order is completed.
                'download_link' => $canDownload ? $product->download_link : null,
            ])->all(),
            'total_price' => $this->total_price,
            'status' => $this->status->value,
            // The customer can pay while the order waits for payment.
            'can_pay' => $this->status === OrderStatus::Pending && $request->user()?->id === $this->user_id,
            'allowed_statuses' => array_map(fn ($s) => $s->value, $this->status->allowedTransitions()),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
