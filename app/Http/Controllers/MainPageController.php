<?php

namespace App\Http\Controllers;

use App\Http\Resources\MainPageResource;
use App\Http\Resources\post\PostAllResource;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\work\WorkAllResource;
use App\Models\Post;
use App\Models\Product;
use App\Models\Work;

class MainPageController extends Controller
{

    public function mainContent()
    {
        $works = Work::orderByDesc('id')->take(4)->get();
        $posts = Post::orderByDesc('id')->take(4)->get();
        $products = Product::orderByDesc('id')->take(4)->get();

        return [
            'works' => WorkAllResource::collection($works),
            'posts' => PostAllResource::collection($posts),
            'products' => ProductAllResource::collection($products),
        ];
    }
}
