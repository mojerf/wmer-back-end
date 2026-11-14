<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\GatewayFactory;
use Illuminate\Support\Facades\DB;

/** Finishes a payment after the customer returns from the gateway. Safe to run more than once. */
final class CompletePayment
{
    public function __construct(private readonly GatewayFactory $gateways) {}

    /**
     * @param  array<string, mixed>  $query  the callback's query string (verified with the gateway, not trusted)
     * @return bool whether the order is paid
     */
    public function __invoke(Payment $payment, array $query): bool
    {
        if ($payment->status === PaymentStatus::Paid) {
            return true; // the customer reloaded the callback URL
        }
        if ($payment->status !== PaymentStatus::Pending) {
            return false;
        }

        $result = $this->gateways->named($payment->gateway)->verify($payment, $query);

        return DB::transaction(function () use ($payment, $result): bool {
            $current = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            // Two requests can race here; the first one wins.
            if ($current->status === PaymentStatus::Paid) {
                return true;
            }
            if ($current->status !== PaymentStatus::Pending) {
                return false;
            }

            if (! $result->paid) {
                $current->update(['status' => PaymentStatus::Failed]);

                return false;
            }

            $current->update([
                'status' => PaymentStatus::Paid,
                'reference' => $result->reference,
                'paid_at' => now(),
            ]);

            // Digital goods are delivered as soon as the money is confirmed. A cancelled order stays
            // cancelled (the payment is still recorded, so an administrator can refund it).
            Order::query()
                ->whereKey($current->order_id)
                ->whereIn('status', [OrderStatus::Pending->value, OrderStatus::Processing->value])
                ->update(['status' => OrderStatus::Completed->value]);

            return true;
        });
    }
}
