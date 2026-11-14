<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Zarinpal REST API v4 (https://docs.zarinpal.com). */
final class ZarinpalGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $merchantId,
        private readonly bool $sandbox = true,
        private readonly string $currency = 'IRT',
    ) {}

    public function name(): string
    {
        return 'zarinpal';
    }

    public function start(Payment $payment, string $callbackUrl): StartResult
    {
        try {
            $data = $this->http()->post($this->api('request'), [
                'merchant_id' => $this->merchantId,
                'amount' => $payment->amount,
                'currency' => $this->currency,
                'callback_url' => $callbackUrl,
                'description' => 'Order #'.$payment->order_id,
            ])->json('data');
        } catch (ConnectionException $e) {
            throw new PaymentException('Payment gateway is unreachable.', 0, $e);
        }

        $authority = is_array($data) ? ($data['authority'] ?? null) : null;

        if (! is_array($data) || ($data['code'] ?? null) !== 100 || ! is_string($authority) || $authority === '') {
            throw new PaymentException('Payment gateway refused the payment.');
        }

        return new StartResult($authority, $this->pay($authority));
    }

    public function verify(Payment $payment, array $query): VerifyResult
    {
        if (($query['Status'] ?? null) !== 'OK') {
            return new VerifyResult(false);
        }

        try {
            $data = $this->http()->post($this->api('verify'), [
                'merchant_id' => $this->merchantId,
                'amount' => $payment->amount,
                'authority' => $payment->authority,
            ])->json('data');
        } catch (ConnectionException) {
            return new VerifyResult(false);
        }

        // 100 = verified now, 101 = already verified earlier.
        $paid = is_array($data) && in_array($data['code'] ?? null, [100, 101], true);

        return new VerifyResult($paid, $paid ? (string) ($data['ref_id'] ?? '') : null);
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(15)->retry(2, 250, throw: false);
    }

    private function api(string $action): string
    {
        $host = $this->sandbox ? 'sandbox.zarinpal.com' : 'payment.zarinpal.com';

        return "https://{$host}/pg/v4/payment/{$action}.json";
    }

    private function pay(string $authority): string
    {
        $host = $this->sandbox ? 'sandbox.zarinpal.com' : 'payment.zarinpal.com';

        return "https://{$host}/pg/StartPay/{$authority}";
    }
}
