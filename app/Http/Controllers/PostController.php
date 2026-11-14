<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\post\PostSingleResource;
use App\Models\Post;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index()
    {
        return PostAllResource::collection(Post::withCount('comments')->latest('id')->paginate(12));
    }

    public function show(Post $post): PostSingleResource
    {
        return new PostSingleResource($post->load(['user', 'comments.user']));
    }

    public function store(PostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create([
            ...$request->safe()->except('image'),
            'image' => ImageStorage::store($request->file('image'), 'posts'),
        ]);

        return response()->json([
            'message' => __('messages.postCreated'),
            'post_id' => $post->getKey(),
        ], 201);
    }

    public function update(PostRequest $request, Post $post): JsonResponse
    {
        $oldImage = $post->image;

        $post->update([
            ...$request->safe()->except('image'),
            'image' => $request->hasFile('image') ? ImageStorage::store($request->file('image'), 'posts') : $oldImage,
        ]);

        ImageStorage::delete($request->hasFile('image') ? $oldImage : null);

        return response()->json(['message' => __('messages.postUpdated'), 'post_id' => $post->id]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();
        ImageStorage::delete($post->image);

        return response()->json(['message' => __('messages.postDeleted')]);
    }
}
