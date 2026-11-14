<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Work;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('comments'));

it('lists top-level comments with their replies and never exposes e-mail addresses', function () {
    $post = Post::factory()->create(['slug' => 'p']);
    $author = customer();
    $top = Comment::factory()->create([
        'commentable_type' => Post::class, 'commentable_id' => $post->id, 'user_id' => $author->id, 'parent_id' => null,
    ]);
    Comment::factory()->create([
        'commentable_type' => Post::class, 'commentable_id' => $post->id, 'user_id' => $author->id, 'parent_id' => $top->id,
    ]);

    $response = $this->getJson('/api/v1/posts/p/comments')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonCount(1, 'data.0.replies');

    expect($response->getContent())->not->toContain($author->email)
        ->and($this->getJson('/api/v1/posts/p')->json('data.comments_count'))->toBe(2);
});

it('rejects unknown content types and slugs', function () {
    $this->getJson('/api/v1/things/x/comments')->assertNotFound();
    $this->getJson('/api/v1/posts/missing/comments')->assertNotFound();
});

it('requires a signed-in user to comment', function () {
    Post::factory()->create(['slug' => 'p']);

    $this->postJson('/api/v1/posts/p/comments', ['body' => 'Nice'])->assertUnauthorized();
});

it('lets a user comment and reply on any content type', function () {
    $user = customer();
    Work::factory()->create(['slug' => 'w']);

    $parent = $this->actingAs($user)->postJson('/api/v1/works/w/comments', ['body' => 'First!'])
        ->assertCreated()->json('comment.id');

    $this->actingAs($user)->postJson('/api/v1/works/w/comments', ['body' => 'Reply', 'parent_id' => $parent])
        ->assertCreated()->assertJsonPath('comment.parent_id', $parent);

    expect(Comment::count())->toBe(2);
});

it('validates comment bodies', function () {
    Post::factory()->create(['slug' => 'p']);

    $this->actingAs(customer())->postJson('/api/v1/posts/p/comments', ['body' => ''])
        ->assertUnprocessable()->assertJsonValidationErrors('body');
    $this->actingAs(customer())->postJson('/api/v1/posts/p/comments', ['body' => str_repeat('x', 2001)])
        ->assertUnprocessable();
});

it('only allows replies to top-level comments of the same item', function () {
    $user = customer();
    $a = Post::factory()->create(['slug' => 'a']);
    Post::factory()->create(['slug' => 'b']);

    $top = Comment::factory()->create(['commentable_type' => Post::class, 'commentable_id' => $a->id, 'user_id' => $user->id]);
    $reply = Comment::factory()->create(['commentable_type' => Post::class, 'commentable_id' => $a->id, 'user_id' => $user->id, 'parent_id' => $top->id]);

    // a comment of item "a" cannot be answered from item "b"
    $this->actingAs($user)->postJson('/api/v1/posts/b/comments', ['body' => 'Hi', 'parent_id' => $top->id])
        ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    // no replies to replies
    $this->actingAs($user)->postJson('/api/v1/posts/a/comments', ['body' => 'Hi', 'parent_id' => $reply->id])
        ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
});

it('lets authors and admins delete comments but nobody else', function () {
    $author = customer();
    $post = Post::factory()->create();
    $make = fn () => Comment::factory()->create([
        'commentable_type' => Post::class, 'commentable_id' => $post->id, 'user_id' => $author->id,
    ]);

    $comment = $make();
    $this->deleteJson('/api/v1/comments/'.$comment->id)->assertUnauthorized();
    $this->actingAs(customer())->deleteJson('/api/v1/comments/'.$comment->id)->assertForbidden();
    $this->actingAs($author)->deleteJson('/api/v1/comments/'.$comment->id)->assertOk();
    expect(Comment::count())->toBe(0);

    $this->actingAs(admin())->deleteJson('/api/v1/comments/'.$make()->id)->assertOk();
});

it('throttles comment spam', function () {
    Post::factory()->create(['slug' => 'p']);
    $user = customer();

    foreach (range(1, 10) as $i) {
        $this->actingAs($user)->postJson('/api/v1/posts/p/comments', ['body' => "Comment $i"])->assertCreated();
    }

    $this->actingAs($user)->postJson('/api/v1/posts/p/comments', ['body' => 'One too many'])->assertTooManyRequests();
});
