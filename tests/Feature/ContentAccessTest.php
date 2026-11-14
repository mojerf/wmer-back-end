<?php

use App\Models\Post;
use App\Models\Product;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('lets anyone read works, posts, products and the main page', function () {
    Work::factory()->count(2)->create();
    Post::factory()->count(2)->create();
    Product::factory()->count(2)->create();

    $this->getJson('/api/v1/works')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/works/'.Work::first()->slug)->assertOk()->assertJsonPath('data.id', Work::first()->id);
    $this->getJson('/api/v1/main-page')->assertOk()->assertJsonStructure(['works', 'posts', 'products']);
});

it('does not expose download links publicly', function () {
    $product = Product::factory()->create(['download_link' => 'https://files.example.test/secret.zip']);

    expect($this->getJson('/api/v1/products')->getContent())->not->toContain('secret.zip');
    expect($this->getJson('/api/v1/products/'.$product->slug)->getContent())->not->toContain('secret.zip');
});

it('blocks writes for guests (401) and regular users (403) and allows admins', function () {
    $payload = [
        'title' => 'A new post',
        'slug' => 'a-new-post',
        'description' => 'Body text',
        'image' => UploadedFile::fake()->image('cover.jpg'),
    ];

    $this->postJson('/api/v1/posts', $payload)->assertUnauthorized();
    $this->actingAs(customer())->postJson('/api/v1/posts', $payload)->assertForbidden();

    $this->actingAs(admin())->postJson('/api/v1/posts', $payload)->assertCreated();
    expect(Post::where('slug', 'a-new-post')->exists())->toBeTrue();
});

it('keeps orders and contact messages admin only', function () {
    $this->getJson('/api/v1/orders')->assertUnauthorized();
    $this->actingAs(customer())->getJson('/api/v1/orders')->assertForbidden();
    $this->actingAs(customer())->getJson('/api/v1/contacts')->assertForbidden();

    $this->actingAs(admin())->getJson('/api/v1/orders')->assertOk();
    $this->actingAs(admin())->getJson('/api/v1/contacts')->assertOk();
});

it('validates uploads: only small images with safe slugs', function () {
    $this->actingAs(admin())->postJson('/api/v1/posts', [
        'title' => 'Bad',
        'slug' => '../etc/passwd',
        'description' => 'x',
        'image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
    ])->assertUnprocessable()->assertJsonValidationErrors(['image', 'slug']);
});

it('replaces and removes work images when updating and deleting', function () {
    $admin = admin();
    $fields = [
        'title' => 'Work title', 'slug' => 'work-title', 'timeline' => 'a', 'publish_date' => 'b',
        'role' => 'dev', 'tags' => 'Angular,Laravel', 'description' => 'text',
    ];

    $this->actingAs($admin)->postJson('/api/v1/works', $fields + ['image' => UploadedFile::fake()->image('one.jpg')])
        ->assertCreated();
    $work = Work::firstWhere('slug', 'work-title');
    Storage::disk('public')->assertExists($work->image);
    $oldImage = $work->image;

    $this->actingAs($admin)->postJson('/api/v1/works/'.$work->id, $fields + [
        '_method' => 'PUT',
        'image' => UploadedFile::fake()->image('two.jpg'),
    ])->assertOk();

    Storage::disk('public')->assertMissing($oldImage);
    Storage::disk('public')->assertExists($work->fresh()->image);

    $current = $work->fresh()->image;
    $this->actingAs($admin)->deleteJson('/api/v1/works/'.$work->id)->assertOk();
    Storage::disk('public')->assertMissing($current);
});
