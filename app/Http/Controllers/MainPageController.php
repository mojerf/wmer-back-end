<?php

namespace App\Http\Controllers;

use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\work\WorkAllResource;
use App\Models\Post;
use App\Models\Product;
use App\Models\Work;
use App\Support\ContentCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MainPageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(ContentCache::remember('main-page', $request, fn (): array => [
            'works' => WorkAllResource::collection(
                Work::select(WorkController::LIST_COLUMNS)->latest('id')->take(4)->get()
            )->resolve($request),
            'posts' => PostAllResource::collection(
                Post::select(PostController::LIST_COLUMNS)->withCount('comments')->latest('id')->take(4)->get()
            )->resolve($request),
            'products' => ProductAllResource::collection(
                Product::select(ProductController::LIST_COLUMNS)->withCount('comments')->latest('id')->take(4)->get()
            )->resolve($request),
        ]));
    }
}
