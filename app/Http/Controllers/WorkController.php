<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListContentRequest;
use App\Http\Requests\WorkRequest;
use App\Http\Resources\work\WorkAllResource;
use App\Http\Resources\work\WorkSingleResource;
use App\Models\Work;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class WorkController extends Controller
{
    public function index(ListContentRequest $request)
    {
        $tag = $request->validated('tag');

        return WorkAllResource::collection(
            Work::query()
                ->search($request->validated('search'), ['title', 'tags'])
                ->when($tag, fn ($query) => $query->whereRaw("tags like ? escape '!'", [Work::containsPattern($tag)]))
                ->latest('id')
                ->paginate(12)
                ->withQueryString()
        );
    }

    public function show(Work $work): WorkSingleResource
    {
        return new WorkSingleResource($work->loadCount('comments'));
    }

    public function store(WorkRequest $request): JsonResponse
    {
        $work = $request->user()->works()->create([
            ...$request->safe()->except(['image', 'full_image']),
            'image' => ImageStorage::store($request->file('image'), 'works'),
            'full_image' => $request->hasFile('full_image')
                ? ImageStorage::store($request->file('full_image'), 'works')
                : null,
        ]);

        return response()->json([
            'message' => __('messages.workCreated'),
            'work_id' => $work->getKey(),
        ], 201);
    }

    public function update(WorkRequest $request, Work $work): JsonResponse
    {
        $oldImage = $work->image;
        $oldFullImage = $work->full_image;

        $work->update([
            ...$request->safe()->except(['image', 'full_image']),
            'image' => $request->hasFile('image') ? ImageStorage::store($request->file('image'), 'works') : $oldImage,
            'full_image' => $request->hasFile('full_image') ? ImageStorage::store($request->file('full_image'), 'works') : $oldFullImage,
        ]);

        ImageStorage::delete(
            $request->hasFile('image') ? $oldImage : null,
            $request->hasFile('full_image') ? $oldFullImage : null,
        );

        return response()->json(['message' => __('messages.workUpdated'), 'work_id' => $work->id]);
    }

    public function destroy(Work $work): JsonResponse
    {
        $work->delete();
        ImageStorage::delete($work->image, $work->full_image);

        return response()->json(['message' => __('messages.workDeleted')]);
    }
}
