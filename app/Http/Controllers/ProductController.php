<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsAdmin;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\product\ProductSingleResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProductAllResource::collection(Product::orderByDesc('id')->paginate(12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', 'unique:products'],
            'price' => ['required', 'integer', 'min:0'],
            'price_with_discount' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'expert' => ['required', 'string'],
            'description' => ['required', 'string'],
            'download_link' => ['nullable', 'string'],
        ]);

        $user_id = Auth::guard('sanctum')->user()->id;
        $product = Product::create([
            'user_id' => $user_id,
            'image' => $request->file('image')->store('products'),
            'title' => $request->title,
            'slug' => $request->slug,
            'price' => $request->price,
            'price_with_discount' => $request->price_with_discount,
            'expert' => $request->expert,
            'description' => $request->description,
            'download_link' => $request->download_link,
        ]);

        return response()->json([
            'message' => __('messages.productCreated'),
            'product_id' => $product->id
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return new ProductSingleResource($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'image' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'title' => ['required', 'string', 'min:3'],
            'slug' => ['required', 'string', 'min:3', Rule::unique('products')->ignore($product->id),],
            'price' => ['required', 'integer', 'min:0'],
            'price_with_discount' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'expert' => ['required', 'string'],
            'description' => ['required', 'string'],
            'download_link' => ['nullable', 'string'],
        ]);

        $oldImagePath = $product->image;

        $product->update([
            'image' => $request->hasFile('image') ? $request->file('image')->store('products') : $product->image,
            'title' => $request->title,
            'slug' => $request->slug,
            'price' => $request->price,
            'price_with_discount' => $request->price_with_discount,
            'expert' => $request->expert,
            'description' => $request->description,
            'download_link' => $request->download_link,
        ]);

        if ($request->hasFile('image') && $oldImagePath) {
            Storage::delete($oldImagePath);
        }

        return response()->json([
            'message' => __('messages.productUpdated'),
            'product' => $product
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->orders()->count() > 0) {
            return response()->json([
                'message' => __('messages.cannotDeleteProductWithOrder'),
            ], 400);
        }

        $product->delete();
        return response()->json(['message' => __('messages.productDeleted')], 200);
    }
    public static function middleware(): array
    {
        return [
            new Middleware(IsAdmin::class, except: ['index', 'show']),
        ];
    }
}
