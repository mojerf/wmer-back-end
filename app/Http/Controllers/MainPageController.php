<?php

namespace App\Http\Controllers;

use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\work\WorkAllResource;
use App\Models\Post;
use App\Models\Product;
use App\Models\Work;

class MainPageController extends Controller
{
    public function __invoke(): array
    {
        return [
            'works' => WorkAllResource::collection(Work::latest('id')->take(4)->get()),
            'posts' => PostAllResource::collection(Post::withCount('comments')->latest('id')->take(4)->get()),
            'products' => ProductAllResource::collection(Product::withCount('comments')->latest('id')->take(4)->get()),
        ];
    }
}
