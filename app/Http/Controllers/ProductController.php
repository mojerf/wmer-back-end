<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\product\ProductSingleResource;
use App\Models\Product;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index()
    {
        return ProductAllResource::collection(Product::withCount('comments')->latest('id')->paginate(12));
    }

    public function show(Product $product): ProductSingleResource
    {
        return new ProductSingleResource($product->load(['user', 'comments.user']));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $product = $request->user()->products()->create([
            ...$request->safe()->except('image'),
            'image' => ImageStorage::store($request->file('image'), 'products'),
        ]);

        return response()->json([
            'message' => __('messages.productCreated'),
            'product_id' => $product->getKey(),
        ], 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $oldImage = $product->image;

        $product->update([
            ...$request->safe()->except('image'),
            'image' => $request->hasFile('image') ? ImageStorage::store($request->file('image'), 'products') : $oldImage,
        ]);

        ImageStorage::delete($request->hasFile('image') ? $oldImage : null);

        return response()->json(['message' => __('messages.productUpdated'), 'product_id' => $product->id]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orders()->exists()) {
            return response()->json(['message' => __('messages.cannotDeleteProductWithOrder')], 409);
        }

        $product->delete();
        ImageStorage::delete($product->image);

        return response()->json(['message' => __('messages.productDeleted')]);
    }
}
