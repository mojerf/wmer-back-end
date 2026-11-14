<?php

namespace App\Http\Controllers;

use App\Actions\CompletePayment;
use App\Actions\StartPayment;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    /** Starts paying one of the signed-in customer's pending orders. */
    public function pay(Order $order, StartPayment $start): JsonResponse
    {
        Gate::authorize('pay', $order);

        try {
            $url = $start($order);
        } catch (PaymentException) {
            return response()->json(['message' => __('messages.paymentUnavailable')], 502);
        }

        return response()->json([
            'message' => $url === null ? __('messages.orderCompleted') : __('messages.paymentStarted'),
            // null when there was nothing to pay (a free order is completed immediately)
            'payment_url' => $url,
            'order' => new OrderResource($order->refresh()->load(['user', 'products'])),
        ]);
    }

    /**
     * Where the gateway sends the customer's browser back to. Nothing here is trusted: the payment is
     * looked up by its gateway token and confirmed with the gateway before the order is touched.
     */
    public function callback(Request $request, CompletePayment $complete): RedirectResponse
    {
        $front = rtrim((string) config('app.frontend_url'), '/');
        $authority = $request->query('Authority');

        $payment = is_string($authority) && $authority !== ''
            ? Payment::query()->where('authority', $authority)->first()
            : null;

        if ($payment === null) {
            return redirect()->away($front.'/orders?payment=failed');
        }

        $paid = $complete($payment, $request->query());

        return redirect()->away(sprintf('%s/orders/%d?payment=%s', $front, $payment->order_id, $paid ? 'success' : 'failed'));
    }
}
