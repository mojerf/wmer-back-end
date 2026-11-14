<?php

namespace App\Payments;

use LogicException;

/** Builds the configured gateway (config/payments.php). */
final class GatewayFactory
{
    public function default(): PaymentGateway
    {
        return $this->named((string) config('payments.gateway'));
    }

    public function named(string $name): PaymentGateway
    {
        return match ($name) {
            'fake' => $this->fake(),
            'zarinpal' => new ZarinpalGateway(
                (string) config('payments.zarinpal.merchant_id'),
                (bool) config('payments.zarinpal.sandbox'),
                (string) config('payments.zarinpal.currency'),
            ),
            default => throw new LogicException("Unknown payment gateway [{$name}]."),
        };
    }

    private function fake(): FakeGateway
    {
        // The fake gateway approves everything: it must never be reachable in production.
        if (app()->isProduction()) {
            throw new LogicException('The fake payment gateway cannot be used in production.');
        }

        return new FakeGateway;
    }
}
