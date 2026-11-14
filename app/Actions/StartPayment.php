<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\GatewayFactory;
use App\Payments\PaymentException;

/** Starts paying an order. The amount always comes from the order, never from the request. */
final class StartPayment
{
    public function __construct(private readonly GatewayFactory $gateways) {}

    /**
     * @return string|null the gateway URL to send the customer to, or null when there is nothing to pay
     *                     (a free order is completed immediately)
     *
     * @throws PaymentException
     */
    public function __invoke(Order $order): ?string
    {
        if ($order->total_price === 0) {
            $order->update(['status' => OrderStatus::Completed]);

            return null;
        }

        $gateway = $this->gateways->default();

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => $gateway->name(),
            'amount' => $order->total_price,
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $result = $gateway->start($payment, route('payments.callback'));
        } catch (PaymentException $e) {
            $payment->update(['status' => PaymentStatus::Failed]);

            throw $e;
        }

        $payment->update(['authority' => $result->authority]);

        return $result->redirectUrl;
    }
}
