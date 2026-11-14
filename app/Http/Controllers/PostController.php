<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListContentRequest;
use App\Http\Requests\PostRequest;
use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\post\PostSingleResource;
use App\Models\Post;
use App\Support\ContentCache;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /** Columns the list resource needs (post bodies can be large and are not loaded for lists). */
    public const LIST_COLUMNS = ['id', 'title', 'slug', 'image', 'image_thumb', 'created_at'];

    public function index(ListContentRequest $request): JsonResponse
    {
        return response()->json(ContentCache::remember('posts', $request, function () use ($request): array {
            $posts = Post::query()
                ->select(self::LIST_COLUMNS)
                ->withCount('comments')
                ->search($request->validated('search'))
                ->latest('id')
                ->paginate(12)
                ->withQueryString();

            return PostAllResource::collection($posts)->response($request)->getData(true);
        }));
    }

    public function show(Post $post): PostSingleResource
    {
        return new PostSingleResource($post->load('user')->loadCount('comments'));
    }

    public function store(PostRequest $request): JsonResponse
    {
        [$image, $thumb] = ImageStorage::storeWithThumb($request->file('image'), 'posts');

        $post = $request->user()->posts()->create([
            ...$request->safe()->except('image'),
            'image' => $image,
            'image_thumb' => $thumb,
        ]);

        return response()->json([
            'message' => __('messages.postCreated'),
            'post_id' => $post->getKey(),
        ], 201);
    }

    public function update(PostRequest $request, Post $post): JsonResponse
    {
        $oldImage = $post->image;
        $oldThumb = $post->image_thumb;

        $attributes = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            [$attributes['image'], $attributes['image_thumb']] = ImageStorage::storeWithThumb($request->file('image'), 'posts');
        }

        $post->update($attributes);

        ImageStorage::delete(
            $request->hasFile('image') ? $oldImage : null,
            $request->hasFile('image') ? $oldThumb : null,
        );

        return response()->json(['message' => __('messages.postUpdated'), 'post_id' => $post->id]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();
        ImageStorage::delete($post->image, $post->image_thumb);

        return response()->json(['message' => __('messages.postDeleted')]);
    }
}
