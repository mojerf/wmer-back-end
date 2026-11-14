<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrder;
use App\Enums\OrderStatus;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /** All orders (admin only, enforced by the route group). */
    public function index()
    {
        return OrderResource::collection(
            Order::with(['user', 'products'])->latest('id')->paginate(10)
        );
    }

    /** The signed-in customer's own orders. */
    public function mine(Request $request)
    {
        return OrderResource::collection(
            $request->user()->orders()->with(['user', 'products'])->latest('id')->paginate(10)
        );
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load(['user', 'products']));
    }

    public function store(StoreOrderRequest $request, CreateOrder $createOrder): JsonResponse
    {
        $order = $createOrder($request->user(), $request->validated('product_ids'));

        return response()->json([
            'message' => __('messages.newOrder'),
            'order' => new OrderResource($order),
        ], 201);
    }

    /** Moves an order along its lifecycle (admin only). Invalid jumps are rejected. */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $next = OrderStatus::from($request->validated('status'));

        if (! $order->status->canTransitionTo($next)) {
            throw ValidationException::withMessages(['status' => __('messages.invalidTransition')]);
        }

        $order->update(['status' => $next]);

        return response()->json([
            'message' => __('messages.orderUpdated'),
            'order' => new OrderResource($order->load(['user', 'products'])),
        ]);
    }
}
