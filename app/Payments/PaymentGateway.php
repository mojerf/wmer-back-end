<?php

namespace App\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /** Short stable name stored with every payment ("fake", "zarinpal"). */
    public function name(): string;

    /**
     * Registers the payment with the gateway.
     *
     * @throws PaymentException when the gateway refuses or cannot be reached
     */
    public function start(Payment $payment, string $callbackUrl): StartResult;

    /**
     * Asks the gateway whether the customer really paid. `$query` is the callback's query string;
     * it is never trusted on its own.
     *
     * @param  array<string, mixed>  $query
     */
    public function verify(Payment $payment, array $query): VerifyResult;
}
