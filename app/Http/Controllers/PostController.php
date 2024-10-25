<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsAdmin;
use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\post\PostSingleResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PostController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PostAllResource::collection(Post::orderByDesc('id')->paginate(12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', 'unique:posts'],
            'description' => ['required', 'string'],
        ]);

        $user_id = Auth::guard('sanctum')->user()->id;
        $post = Post::create([
            'user_id' => $user_id,
            'image' => $request->file('image')->store('posts', 'public'),
            'title' => $request->title,
            'slug' => $request->slug,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => __('messages.postCreated'),
            'post_id' => $post->id
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        return new PostSingleResource($post);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        return $post;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $request->validate([
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', Rule::unique('posts')->ignore($post->id),],
            'description' => ['required', 'string'],
        ]);

        $oldImagePath = $post->image;

        $post->update([
            'image' => $request->hasFile('image') ? $request->file('image')->store('posts', 'public') : $post->image,
            'title' => $request->title,
            'slug' => $request->slug,
            'description' => $request->description,
        ]);

        if ($request->hasFile('image') && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return response()->json([
            'message' => __('messages.postUpdated'),
            'post' => $post
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $imagePath = $post->image;
        $post->delete();
        Storage::disk('public')->delete($imagePath);
        return response()->json(['message' => __('messages.postDeleted')], 201);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(IsAdmin::class, except: ['index', 'show']),
        ];
    }
}
