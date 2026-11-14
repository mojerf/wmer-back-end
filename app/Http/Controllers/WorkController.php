<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListContentRequest;
use App\Http\Requests\WorkRequest;
use App\Http\Resources\work\WorkAllResource;
use App\Http\Resources\work\WorkSingleResource;
use App\Models\Work;
use App\Support\ContentCache;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class WorkController extends Controller
{
    /** Columns the list resource needs (long text columns are not loaded for lists). */
    public const LIST_COLUMNS = ['id', 'title', 'slug', 'image', 'image_thumb', 'tags', 'created_at'];

    public function index(ListContentRequest $request): JsonResponse
    {
        return response()->json(ContentCache::remember('works', $request, function () use ($request): array {
            $tag = $request->validated('tag');

            $works = Work::query()
                ->select(self::LIST_COLUMNS)
                ->search($request->validated('search'), ['title', 'tags'])
                ->when($tag, fn ($query) => $query->whereRaw("tags like ? escape '!'", [Work::containsPattern($tag)]))
                ->latest('id')
                ->paginate(12)
                ->withQueryString();

            return WorkAllResource::collection($works)->response($request)->getData(true);
        }));
    }

    public function show(Work $work): WorkSingleResource
    {
        return new WorkSingleResource($work->loadCount('comments'));
    }

    public function store(WorkRequest $request): JsonResponse
    {
        [$image, $thumb] = ImageStorage::storeWithThumb($request->file('image'), 'works');

        $work = $request->user()->works()->create([
            ...$request->safe()->except(['image', 'full_image']),
            'image' => $image,
            'image_thumb' => $thumb,
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
        $oldThumb = $work->image_thumb;
        $oldFullImage = $work->full_image;

        $attributes = $request->safe()->except(['image', 'full_image']);

        if ($request->hasFile('image')) {
            [$attributes['image'], $attributes['image_thumb']] = ImageStorage::storeWithThumb($request->file('image'), 'works');
        }
        if ($request->hasFile('full_image')) {
            $attributes['full_image'] = ImageStorage::store($request->file('full_image'), 'works');
        }

        $work->update($attributes);

        ImageStorage::delete(
            $request->hasFile('image') ? $oldImage : null,
            $request->hasFile('image') ? $oldThumb : null,
            $request->hasFile('full_image') ? $oldFullImage : null,
        );

        return response()->json(['message' => __('messages.workUpdated'), 'work_id' => $work->id]);
    }

    public function destroy(Work $work): JsonResponse
    {
        $work->delete();
        ImageStorage::delete($work->image, $work->image_thumb, $work->full_image);

        return response()->json(['message' => __('messages.workDeleted')]);
    }
}
