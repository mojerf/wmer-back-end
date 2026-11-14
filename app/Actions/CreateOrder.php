<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Creates an order from product ids. Prices always come from the database, never from the client. */
final class CreateOrder
{
    /** @param  array<int, int|string>  $productIds */
    public function __invoke(User $user, array $productIds): Order
    {
        return DB::transaction(function () use ($user, $productIds): Order {
            $products = Product::query()
                ->whereIn('id', array_unique($productIds))
                ->get()
                ->keyBy('id');

            $prices = $products->map(fn (Product $product): int => $product->price_with_discount ?? $product->price);

            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'total_price' => $prices->sum(),
            ]);

            $order->products()->attach(
                $prices->map(fn (int $price): array => ['op_price' => $price])->all()
            );

            return $order->load(['user', 'products']);
        });
    }
}
