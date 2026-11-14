<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Work;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Cache::flush();
});

/** Runs the callback and returns how many SQL statements it executed, plus the statements. */
function queries(Closure $callback): array
{
    $sql = [];
    DB::listen(function ($query) use (&$sql) {
        $sql[] = $query->sql;
    });
    $callback();

    return $sql;
}

it('creates a 480px WebP thumbnail on upload and cleans it up on update and delete', function () {
    $admin = admin();

    $this->actingAs($admin)->postJson('/api/v1/posts', [
        'title' => 'With cover', 'slug' => 'with-cover', 'description' => 'Body',
        'image' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
    ])->assertCreated();

    $post = Post::firstWhere('slug', 'with-cover');
    Storage::disk('public')->assertExists($post->image);
    Storage::disk('public')->assertExists($post->image_thumb);
    expect(getimagesizefromstring(Storage::disk('public')->get($post->image_thumb))[0])->toBe(480);

    $oldThumb = $post->image_thumb;
    $this->actingAs($admin)->postJson('/api/v1/posts/'.$post->id, [
        '_method' => 'PUT', 'title' => 'With cover', 'slug' => 'with-cover', 'description' => 'Body',
        'image' => UploadedFile::fake()->image('new.jpg', 1000, 600),
    ])->assertOk();

    Storage::disk('public')->assertMissing($oldThumb);
    Storage::disk('public')->assertExists($post->fresh()->image_thumb);

    $thumb = $post->fresh()->image_thumb;
    $this->actingAs($admin)->deleteJson('/api/v1/posts/'.$post->id)->assertOk();
    Storage::disk('public')->assertMissing($thumb);
});

it('rejects absurdly large images before they reach GD', function () {
    $this->actingAs(admin())->postJson('/api/v1/posts', [
        'title' => 'Huge', 'slug' => 'huge', 'description' => 'x',
        'image' => UploadedFile::fake()->image('huge.jpg', 7000, 100),
    ])->assertUnprocessable()->assertJsonValidationErrors('image');
});

it('serves the thumbnail in lists and falls back to the full image for older content', function () {
    Post::factory()->create(['slug' => 'new', 'image' => 'posts/a.jpg', 'image_thumb' => 'thumbs/posts/a.jpg.webp']);
    Post::factory()->create(['slug' => 'old', 'image' => 'posts/b.jpg', 'image_thumb' => null]);

    $items = collect($this->getJson('/api/v1/posts')->json('data'))->keyBy('slug');

    expect($items['new']['image_thumb'])->toEndWith('/storage/thumbs/posts/a.jpg.webp')
        ->and($items['old']['image_thumb'])->toEndWith('/storage/posts/b.jpg');
});

it('does not load long text columns for lists', function () {
    Post::factory()->count(3)->create();
    Work::factory()->count(3)->create();
    Product::factory()->count(3)->create();

    $sql = implode("\n", queries(function () {
        $this->getJson('/api/v1/posts');
        $this->getJson('/api/v1/works');
        $this->getJson('/api/v1/products');
    }));

    expect($sql)->not->toContain('"description"')
        ->and($sql)->not->toContain('"overview"')
        ->and($sql)->not->toContain('"learn"')
        ->and($sql)->not->toContain('"expert"')
        ->and($sql)->not->toContain('"download_link"');
});

it('answers list endpoints with a fixed number of queries and none when cached', function () {
    Post::factory()->count(5)->create();
    Comment::factory()->count(5)->create(['commentable_type' => Post::class, 'commentable_id' => Post::first()->id, 'user_id' => customer()->id]);

    $cold = queries(fn () => $this->getJson('/api/v1/posts')->assertOk());
    $warm = queries(fn () => $this->getJson('/api/v1/posts')->assertOk());

    expect(count($cold))->toBeLessThanOrEqual(2)
        ->and($warm)->toBe([]);
});

it('builds the main page from three queries and caches it', function () {
    Work::factory()->count(2)->create();
    Post::factory()->count(2)->create();
    Product::factory()->count(2)->create();

    $cold = queries(fn () => $this->getJson('/api/v1/main-page')->assertOk());
    $warm = queries(fn () => $this->getJson('/api/v1/main-page')->assertOk());

    expect(count($cold))->toBe(3)->and($warm)->toBe([]);
});

it('keeps cached lists fresh: any change to content or comments invalidates them', function () {
    $post = Post::factory()->create(['slug' => 'fresh', 'title' => 'Before']);

    expect($this->getJson('/api/v1/posts')->json('data.0.title'))->toBe('Before');

    $post->update(['title' => 'After']);
    expect($this->getJson('/api/v1/posts')->json('data.0.title'))->toBe('After');

    Comment::factory()->create(['commentable_type' => Post::class, 'commentable_id' => $post->id, 'user_id' => customer()->id]);
    expect($this->getJson('/api/v1/posts')->json('data.0.comments_count'))->toBe(1);

    Post::factory()->create(['slug' => 'another']);
    expect($this->getJson('/api/v1/posts')->json('meta.total'))->toBe(2);

    $post->delete();
    expect($this->getJson('/api/v1/posts')->json('meta.total'))->toBe(1);
});

it('caches each query string separately', function () {
    Post::factory()->create(['slug' => 'a', 'title' => 'Angular signals']);
    Post::factory()->create(['slug' => 'b', 'title' => 'Laravel queues']);

    expect($this->getJson('/api/v1/posts?search=angular')->json('meta.total'))->toBe(1)
        ->and($this->getJson('/api/v1/posts?search=laravel')->json('data.0.slug'))->toBe('b')
        ->and($this->getJson('/api/v1/posts')->json('meta.total'))->toBe(2);
});

it('enforces unique slugs in the database, not only in validation', function () {
    Post::factory()->create(['slug' => 'same']);

    expect(fn () => Post::factory()->create(['slug' => 'same']))->toThrow(QueryException::class);
});

it('loads comments with a constant number of queries however many there are', function () {
    $post = Post::factory()->create(['slug' => 'busy']);
    $author = customer();
    $make = fn (int $n) => Comment::factory()->count($n)->create([
        'commentable_type' => Post::class, 'commentable_id' => $post->id, 'user_id' => $author->id,
    ]);

    $make(2);
    $few = count(queries(fn () => $this->getJson('/api/v1/posts/busy/comments')->assertOk()));

    $make(15);
    $many = count(queries(fn () => $this->getJson('/api/v1/posts/busy/comments')->assertOk()));

    expect($many)->toBe($few);
});
