<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsAdmin;
use App\Http\Resources\work\WorkAllResource;
use App\Http\Resources\work\WorkSingleResource;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class WorkController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return WorkAllResource::collection(Work::orderByDesc('id')->paginate(12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'full_image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', 'unique:works'],
            'timeline' => ['required', 'string'],
            'publish_date' => ['required', 'string'],
            'role' => ['required', 'string'],
            'tags' => ['required', 'string'],
            'project_link' => ['nullable', 'string'],
            'overview' => ['nullable', 'string'],
            'learn' => ['nullable', 'string'],
            'description' => ['required', 'string'],
        ]);

        $user_id = Auth::guard('sanctum')->user()->id;
        $work = Work::create([
            'user_id' => $user_id,
            'image' => $request->file('image')->store('works'),
            'full_image' => $request->hasFile('full_image') ? $request->file('full_image')->store('works') : null,
            'title' => $request->title,
            'slug' => $request->slug,
            'timeline' => $request->timeline,
            'publish_date' => $request->publish_date,
            'role' => $request->role,
            'tags' => $request->tags,
            'project_link' => $request->project_link,
            'overview' => $request->overview,
            'learn' => $request->learn,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => __('messages.workCreated'),
            'work_id' => $work
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Work $work)
    {
        return new WorkSingleResource($work);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Work $work)
    {
        return $work;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Work $work)
    {
        $request->validate([
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'full_image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', Rule::unique('works')->ignore($work->id),],
            'timeline' => ['required', 'string'],
            'publish_date' => ['required', 'string'],
            'role' => ['required', 'string'],
            'tags' => ['required', 'string'],
            'project_link' => ['nullable', 'string'],
            'overview' => ['nullable', 'string'],
            'learn' => ['nullable', 'string'],
            'description' => ['required', 'string'],
        ]);

        $oldImagePath = $work->image;
        $oldFullImagePath = $work->full_image;

        $work->update([
            'image' => $request->hasFile('image') ? $request->file('image')->store('works') : $work->image,
            'full_image' => $request->hasFile('full_image') ? $request->file('full_image')->store('works') : $work->full_image,
            'title' => $request->title,
            'slug' => $request->slug,
            'timeline' => $request->timeline,
            'publish_date' => $request->publish_date,
            'role' => $request->role,
            'tags' => $request->tags,
            'project_link' => $request->project_link,
            'overview' => $request->overview,
            'learn' => $request->learn,
            'description' => $request->description,
        ]);

        if ($request->hasFile('image') && $oldImagePath) {
            Storage::delete($oldImagePath);
        }
        if ($request->hasFile('full_image') && $oldFullImagePath) {
            Storage::delete($oldFullImagePath);
        }

        return response()->json([
            'message' => __('messages.workUpdated'),
            'work' => $work
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Work $work)
    {
        $work->delete();
        return response()->json(['message' => __('messages.workDeleted')], 200);
    }

    public static function middleware(): array
    {
        return [
            new Middleware(IsAdmin::class, except: ['index', 'show']),
        ];
    }
}
