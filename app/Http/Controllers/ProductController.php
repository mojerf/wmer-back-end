<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListContentRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\product\ProductAllResource;
use App\Http\Resources\product\ProductSingleResource;
use App\Models\Product;
use App\Support\ContentCache;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /** Columns the list resource needs (descriptions are not loaded for lists). */
    public const LIST_COLUMNS = ['id', 'title', 'slug', 'image', 'image_thumb', 'price', 'price_with_discount', 'created_at'];

    public function index(ListContentRequest $request): JsonResponse
    {
        return response()->json(ContentCache::remember('products', $request, function () use ($request): array {
            $price = 'coalesce(price_with_discount, price)';

            $products = Product::query()
                ->select(self::LIST_COLUMNS)
                ->withCount('comments')
                ->search($request->validated('search'), ['title', 'expert'])
                ->when(
                    $request->validated('sort'),
                    fn ($query, string $sort) => match ($sort) {
                        'oldest' => $query->oldest('id'),
                        'price_asc' => $query->orderByRaw("$price asc, id desc"),
                        'price_desc' => $query->orderByRaw("$price desc, id desc"),
                        default => $query->latest('id'),
                    },
                    fn ($query) => $query->latest('id'),
                )
                ->paginate(12)
                ->withQueryString();

            return ProductAllResource::collection($products)->response($request)->getData(true);
        }));
    }

    public function show(Product $product): ProductSingleResource
    {
        return new ProductSingleResource($product->load('user')->loadCount('comments'));
    }

    public function store(ProductRequest $request): JsonResponse
    {
        [$image, $thumb] = ImageStorage::storeWithThumb($request->file('image'), 'products');

        $product = $request->user()->products()->create([
            ...$request->safe()->except('image'),
            'image' => $image,
            'image_thumb' => $thumb,
        ]);

        return response()->json([
            'message' => __('messages.productCreated'),
            'product_id' => $product->getKey(),
        ], 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $oldImage = $product->image;
        $oldThumb = $product->image_thumb;

        $attributes = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            [$attributes['image'], $attributes['image_thumb']] = ImageStorage::storeWithThumb($request->file('image'), 'products');
        }

        $product->update($attributes);

        ImageStorage::delete(
            $request->hasFile('image') ? $oldImage : null,
            $request->hasFile('image') ? $oldThumb : null,
        );

        return response()->json(['message' => __('messages.productUpdated'), 'product_id' => $product->id]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orders()->exists()) {
            return response()->json(['message' => __('messages.cannotDeleteProductWithOrder')], 409);
        }

        $product->delete();
        ImageStorage::delete($product->image, $product->image_thumb);

        return response()->json(['message' => __('messages.productDeleted')]);
    }
}
