<?php

namespace App\Http\Controllers;

use App\Http\Middleware\IsAdmin;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return OrderResource::collection(Order::orderByDesc('id')->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $user_id = Auth::guard('sanctum')->user()->id;
        $products = Product::findMany($request->product_ids);
        $total_price = 0;

        foreach ($products as $product) {
            $price = $product->price_with_discount ?? $product->price;
            $total_price += $price;
        }

        $order = Order::create([
            'user_id' => $user_id,
            'status' => 'pending',
            'total_price' => $total_price,
        ]);

        foreach ($products as $product) {
            $price = $product->price_with_discount ?? $product->price;
            $order->products()->attach($product->id, [
                'op_price' => $price,
            ]);
        }

        return response()->json(new OrderResource($order), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    public static function middleware(): array
    {
        return [
            'auth:sanctum',
            new Middleware(IsAdmin::class, except: ['store']),
        ];
    }
}
