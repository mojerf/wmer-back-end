<?php

use App\Models\Post;
use App\Models\Product;
use App\Models\Work;

it('serves single items by slug and returns 404 for unknown slugs', function () {
    $post = Post::factory()->create(['slug' => 'hello-world']);

    $this->getJson('/api/v1/posts/hello-world')
        ->assertOk()
        ->assertJsonPath('data.id', $post->id)
        ->assertJsonPath('data.slug', 'hello-world');

    $this->getJson('/api/v1/posts/nope')->assertNotFound();
});

it('searches by title with wildcard characters treated literally', function () {
    Post::factory()->create(['title' => 'Angular signals deep dive', 'slug' => 'a']);
    Post::factory()->create(['title' => 'Laravel queues', 'slug' => 'b']);
    Post::factory()->create(['title' => '100% coverage', 'slug' => 'c']);

    $this->getJson('/api/v1/posts?search=signals')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'a');
    $this->getJson('/api/v1/posts?search=100%25')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'c');
    $this->getJson('/api/v1/posts?search=%25')->assertOk()->assertJsonCount(1, 'data');
});

it('filters works by tag', function () {
    Work::factory()->create(['tags' => 'Angular,Laravel', 'slug' => 'one']);
    Work::factory()->create(['tags' => 'Vue', 'slug' => 'two']);

    $this->getJson('/api/v1/works?tag=Angular')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tags', ['Angular', 'Laravel']);
});

it('sorts products by the price the customer actually pays', function () {
    Product::factory()->create(['slug' => 'mid', 'price' => 500, 'price_with_discount' => null]);
    Product::factory()->create(['slug' => 'cheap', 'price' => 900, 'price_with_discount' => 100]);
    Product::factory()->create(['slug' => 'dear', 'price' => 1000, 'price_with_discount' => null]);

    $asc = collect($this->getJson('/api/v1/products?sort=price_asc')->json('data'))->pluck('slug')->all();
    $desc = collect($this->getJson('/api/v1/products?sort=price_desc')->json('data'))->pluck('slug')->all();

    expect($asc)->toBe(['cheap', 'mid', 'dear'])->and($desc)->toBe(['dear', 'mid', 'cheap']);
});

it('rejects invalid query parameters', function () {
    $this->getJson('/api/v1/products?sort=drop-table')->assertUnprocessable()->assertJsonValidationErrors('sort');
    $this->getJson('/api/v1/posts?search='.str_repeat('a', 101))->assertUnprocessable();
});

it('exposes the discounted price as price and the original as old_price', function () {
    Product::factory()->create(['slug' => 'sale', 'price' => 2000, 'price_with_discount' => 1500]);
    Product::factory()->create(['slug' => 'plain', 'price' => 800, 'price_with_discount' => null]);

    $items = collect($this->getJson('/api/v1/products')->json('data'))->keyBy('slug');

    expect($items['sale'])->toMatchArray(['price' => 1500, 'old_price' => 2000, 'has_discount' => true])
        ->and($items['plain'])->toMatchArray(['price' => 800, 'old_price' => null, 'has_discount' => false]);
});

it('returns ISO 8601 timestamps and paginates', function () {
    Post::factory()->count(15)->create();

    $response = $this->getJson('/api/v1/posts')->assertOk()
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    expect($response->json('data'))->toHaveCount(12)
        ->and($response->json('data.0.created_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T/');
    $this->getJson('/api/v1/posts?page=2')->assertOk()->assertJsonCount(3, 'data');
});
