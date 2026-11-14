<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Work;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Comments on works, posts and products (the `{type}` segment of the URL).
 * Replies are one level deep: a reply's parent must be a top-level comment of the same item.
 */
class CommentController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const TYPES = [
        'works' => Work::class,
        'posts' => Post::class,
        'products' => Product::class,
    ];

    public function index(string $type, string $slug)
    {
        $item = $this->item($type, $slug);

        return CommentResource::collection(
            $item->comments()
                ->whereNull('parent_id')
                ->with(['user', 'replies.user'])
                ->paginate(20)
        );
    }

    public function store(StoreCommentRequest $request, string $type, string $slug): JsonResponse
    {
        $item = $this->item($type, $slug);
        $parentId = $request->validated('parent_id');

        if ($parentId !== null) {
            $parent = Comment::find($parentId);

            if (! $parent
                || $parent->parent_id !== null
                || $parent->commentable_type !== $item->getMorphClass()
                || $parent->commentable_id !== $item->getKey()) {
                throw ValidationException::withMessages(['parent_id' => __('messages.invalidParentComment')]);
            }
        }

        $comment = $item->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $request->validated('body'),
        ]);

        return response()->json([
            'message' => __('messages.commentCreated'),
            'comment' => new CommentResource($comment->load('user')),
        ], 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => __('messages.commentDeleted')]);
    }

    private function item(string $type, string $slug): Work|Post|Product
    {
        $class = self::TYPES[$type] ?? abort(404);

        return $class::query()->where('slug', $slug)->firstOrFail();
    }
}
