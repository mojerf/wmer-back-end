<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;

it('requires authentication to place an order', function () {
    $product = Product::factory()->create();

    $this->postJson('/api/v1/orders', ['product_ids' => [$product->id]])->assertUnauthorized();
});

it('prices the order from the database and ignores client supplied prices', function () {
    $full = Product::factory()->create(['price' => 100_000, 'price_with_discount' => null]);
    $discounted = Product::factory()->create(['price' => 200_000, 'price_with_discount' => 150_000]);
    $user = customer();

    $response = $this->actingAs($user)->postJson('/api/v1/orders', [
        'product_ids' => [$full->id, $discounted->id],
        'total_price' => 1,
        'status' => 'completed',
    ]);

    $response->assertCreated()
        ->assertJsonPath('order.total_price', 250_000)
        ->assertJsonPath('order.status', 'pending');

    $order = Order::first();
    expect($order->user_id)->toBe($user->id)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->products)->toHaveCount(2);
});

it('rejects empty, duplicated and unknown product ids', function () {
    $product = Product::factory()->create();

    $this->actingAs(customer())->postJson('/api/v1/orders', ['product_ids' => []])
        ->assertUnprocessable()->assertJsonValidationErrors('product_ids');
    $this->actingAs(customer())->postJson('/api/v1/orders', ['product_ids' => [$product->id, $product->id]])
        ->assertUnprocessable();
    $this->actingAs(customer())->postJson('/api/v1/orders', ['product_ids' => [999_999]])
        ->assertUnprocessable();

    expect(Order::count())->toBe(0);
});

it('hands out download links only for completed orders', function () {
    $product = Product::factory()->create(['download_link' => 'https://files.example.test/file.zip']);

    $pending = Order::factory()->create(['status' => OrderStatus::Pending]);
    $pending->products()->attach($product->id, ['op_price' => 1000]);
    $completed = Order::factory()->create(['status' => OrderStatus::Completed]);
    $completed->products()->attach($product->id, ['op_price' => 1000]);

    $orders = collect($this->actingAs(admin())->getJson('/api/v1/orders')->assertOk()->json('data'))
        ->keyBy('id');

    expect($orders[$pending->id]['products'][0]['download_link'])->toBeNull()
        ->and($orders[$completed->id]['products'][0]['download_link'])->toBe('https://files.example.test/file.zip');
});

it('refuses to delete a product that has orders', function () {
    $product = Product::factory()->create();
    Order::factory()->create()->products()->attach($product->id, ['op_price' => 1000]);

    $this->actingAs(admin())->deleteJson('/api/v1/products/'.$product->id)->assertStatus(409);
    expect(Product::count())->toBe(1);
});
