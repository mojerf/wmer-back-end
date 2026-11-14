<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /** All orders (admin only, enforced by the route group). */
    public function index()
    {
        return OrderResource::collection(
            Order::with(['user', 'products'])->latest('id')->paginate(10)
        );
    }

    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $order = $createOrder($request->user(), $request->validated('product_ids'));

        return response()->json([
            'message' => __('messages.newOrder'),
            'order' => new OrderResource($order),
        ], 201);
    }
}
