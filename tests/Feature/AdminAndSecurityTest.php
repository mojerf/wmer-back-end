<?php

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;

it('reports counts and revenue from completed orders only', function () {
    Post::factory()->count(2)->create();
    Contact::factory()->count(3)->create();
    Order::factory()->create(['status' => OrderStatus::Completed, 'total_price' => 700]);
    Order::factory()->create(['status' => OrderStatus::Completed, 'total_price' => 300]);
    Order::factory()->create(['status' => OrderStatus::Pending, 'total_price' => 5000]);

    $stats = $this->actingAs(admin())->getJson('/api/v1/admin/stats')->assertOk()->json();

    expect($stats['revenue'])->toBe(1000)
        ->and($stats['counts']['posts'])->toBe(2)
        ->and($stats['counts']['contacts'])->toBe(3)
        ->and($stats['counts']['orders'])->toBe(3)
        ->and($stats['orders_by_status'])->toMatchArray(['completed' => 2, 'pending' => 1, 'processing' => 0, 'cancelled' => 0]);
});

it('keeps the statistics admin only', function () {
    $this->getJson('/api/v1/admin/stats')->assertUnauthorized();
    $this->actingAs(customer())->getJson('/api/v1/admin/stats')->assertForbidden();
});

it('sends hardening headers on every API response', function () {
    $response = $this->getJson('/api/v1/main-page')->assertOk();

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

it('does not reveal the framework version or serve HTML errors', function () {
    $this->get('/')->assertNotFound();

    $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
});

it('documents the API for administrators only (outside local development)', function () {
    $this->getJson('/docs/api.json')->assertForbidden();

    $spec = $this->actingAs(admin())->getJson('/docs/api.json')->assertOk()->json();

    expect($spec)->toHaveKey('openapi')
        ->and(array_keys($spec['paths']))->toContain('/main-page', '/works', '/orders/{order}/status');
});

it('gives admins the raw editable fields of an item, download link included', function () {
    $product = Product::factory()->create(['download_link' => 'https://files.example.test/a.zip']);

    $this->actingAs(admin())->getJson("/api/v1/admin/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.download_link', 'https://files.example.test/a.zip')
        ->assertJsonPath('data.slug', $product->slug)
        ->assertJsonMissingPath('data.user_id');

    $this->actingAs(admin())->getJson('/api/v1/admin/products/999999')->assertNotFound();
    $this->actingAs(admin())->getJson('/api/v1/admin/users/1')->assertNotFound();
});

it('keeps the raw item endpoint admin only', function () {
    $product = Product::factory()->create();

    $this->getJson("/api/v1/admin/products/{$product->id}")->assertUnauthorized();
    $this->actingAs(customer())->getJson("/api/v1/admin/products/{$product->id}")->assertForbidden();
});
