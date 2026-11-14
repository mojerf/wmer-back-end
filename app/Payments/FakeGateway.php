<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Development gateway: "approves" the payment by sending the customer straight back to the callback.
 * The callback still goes through the normal verification, so the whole flow is exercised.
 */
final class FakeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function start(Payment $payment, string $callbackUrl): StartResult
    {
        $authority = 'FAKE-'.Str::upper(Str::random(24));

        return new StartResult(
            $authority,
            $callbackUrl.'?'.http_build_query(['Authority' => $authority, 'Status' => 'OK']),
        );
    }

    public function verify(Payment $payment, array $query): VerifyResult
    {
        $approved = ($query['Status'] ?? null) === 'OK'
            && hash_equals((string) $payment->authority, (string) ($query['Authority'] ?? ''));

        return new VerifyResult($approved, $approved ? 'FAKE-'.$payment->id : null);
    }
}
