<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;

it('shows customers only their own orders', function () {
    $mine = customer();
    $theirs = customer();
    Order::factory()->count(2)->create(['user_id' => $mine->id]);
    Order::factory()->create(['user_id' => $theirs->id]);

    $this->getJson('/api/v1/orders/mine')->assertUnauthorized();
    $this->actingAs($mine)->getJson('/api/v1/orders/mine')->assertOk()->assertJsonCount(2, 'data');
});

it('protects single orders: owner and admin only', function () {
    $owner = customer();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    // guests first: actingAs() stays in effect for the rest of the test
    $this->getJson('/api/v1/orders/'.$order->id)->assertUnauthorized();
    $this->actingAs(customer())->getJson('/api/v1/orders/'.$order->id)->assertForbidden();
    $this->actingAs($owner)->getJson('/api/v1/orders/'.$order->id)->assertOk()->assertJsonPath('data.id', $order->id);
    $this->actingAs(admin())->getJson('/api/v1/orders/'.$order->id)->assertOk();
});

it('gives the owner the download link once the order is completed', function () {
    $owner = customer();
    $product = Product::factory()->create(['download_link' => 'https://files.example.test/book.pdf']);
    $order = Order::factory()->create(['user_id' => $owner->id, 'status' => OrderStatus::Completed]);
    $order->products()->attach($product->id, ['op_price' => 10]);

    $this->actingAs($owner)->getJson('/api/v1/orders/'.$order->id)
        ->assertJsonPath('data.products.0.download_link', 'https://files.example.test/book.pdf');
});

it('moves orders through valid transitions only', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $admin = admin();

    $this->actingAs($admin)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'completed'])
        ->assertUnprocessable()->assertJsonValidationErrors('status');

    $this->actingAs($admin)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
        ->assertOk()->assertJsonPath('order.status', 'processing')
        ->assertJsonPath('order.allowed_statuses', ['completed', 'cancelled']);

    $this->actingAs($admin)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'completed'])->assertOk();

    // completed is final
    $this->actingAs($admin)->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'cancelled'])
        ->assertUnprocessable();
    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('rejects unknown statuses and non-admin callers', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    // guests first: actingAs() stays in effect for the rest of the test
    $this->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])->assertUnauthorized();
    $this->actingAs(customer())->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
        ->assertForbidden();
    $this->actingAs(admin())->patchJson("/api/v1/orders/{$order->id}/status", ['status' => 'refunded'])
        ->assertUnprocessable();
});

it('knows its lifecycle', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Processing))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Completed))->toBeFalse()
        ->and(OrderStatus::Cancelled->allowedTransitions())->toBe([]);
});
