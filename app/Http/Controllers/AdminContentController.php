<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Models\Work;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;

/** The raw, editable fields of one item, for the admin edit forms (public resources hide some of them, e.g. the download link). */
class AdminContentController extends Controller
{
    /** @var array<string, class-string<Work|Post|Product>> */
    private const MODELS = ['works' => Work::class, 'posts' => Post::class, 'products' => Product::class];

    public function show(string $type, int $id): JsonResponse
    {
        $model = (self::MODELS[$type] ?? abort(404))::query()->findOrFail($id);

        $data = $model->makeHidden(['user_id', 'image_thumb'])->toArray();
        $data['image'] = ImageStorage::url($model->image);
        if (array_key_exists('full_image', $data)) {
            $data['full_image'] = ImageStorage::url($model->full_image);
        }

        return response()->json(['data' => $data]);
    }
}
