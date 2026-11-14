<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Payments\GatewayFactory;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'payments.gateway' => 'fake',
        'app.frontend_url' => 'https://app.example.test',
    ]);
});

/** A pending order of $user containing one digital product with a download link. */
function pendingOrder(?User $user = null, int $total = 250_000): Order
{
    $user ??= customer();
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending, 'total_price' => $total]);
    $product = Product::factory()->create(['download_link' => 'https://files.example.test/book.pdf']);
    $order->products()->attach($product->id, ['op_price' => $total]);

    return $order;
}

/** Visits a gateway URL (path and query only) as the browser would. */
function openGatewayUrl(string $url)
{
    $parts = parse_url($url);

    return test()->get($parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : ''));
}

it('requires a signed-in owner of a pending order to start paying', function () {
    $order = pendingOrder();

    $this->postJson("/api/v1/orders/{$order->id}/pay")->assertUnauthorized();
    $this->actingAs(customer())->postJson("/api/v1/orders/{$order->id}/pay")->assertForbidden();

    $order->update(['status' => OrderStatus::Completed]);
    $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")->assertForbidden();

    expect(Payment::count())->toBe(0);
});

it('pays an order end to end with the fake gateway and unlocks the download', function () {
    $order = pendingOrder();
    $owner = $order->user;

    $url = $this->actingAs($owner)->postJson("/api/v1/orders/{$order->id}/pay")
        ->assertOk()->assertJsonPath('order.can_pay', true)->json('payment_url');

    $payment = Payment::firstOrFail();
    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe(250_000)
        ->and($url)->toContain('/api/v1/payments/callback')->toContain($payment->fresh()->authority);

    openGatewayUrl($url)->assertRedirect("https://app.example.test/orders/{$order->id}?payment=success");

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->reference)->not->toBeNull()
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Completed);

    $this->actingAs($owner)->getJson("/api/v1/orders/{$order->id}")
        ->assertJsonPath('data.can_pay', false)
        ->assertJsonPath('data.products.0.download_link', 'https://files.example.test/book.pdf');
});

it('takes the amount from the order, never from the request', function () {
    $order = pendingOrder(total: 90_000);

    $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay", ['amount' => 1, 'total_price' => 1])->assertOk();

    expect(Payment::first()->amount)->toBe(90_000);
});

it('leaves the order pending when the payment is declined, and allows another attempt', function () {
    $order = pendingOrder();
    $owner = $order->user;

    $url = $this->actingAs($owner)->postJson("/api/v1/orders/{$order->id}/pay")->json('payment_url');
    $declined = str_replace('Status=OK', 'Status=NOK', $url);

    openGatewayUrl($declined)->assertRedirect("https://app.example.test/orders/{$order->id}?payment=failed");

    expect(Payment::first()->status)->toBe(PaymentStatus::Failed)
        ->and($order->fresh()->status)->toBe(OrderStatus::Pending);

    $this->actingAs($owner)->postJson("/api/v1/orders/{$order->id}/pay")->assertOk();
    expect(Payment::count())->toBe(2);
});

it('is idempotent: reloading the callback does not pay or deliver twice', function () {
    $order = pendingOrder();
    $url = $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")->json('payment_url');

    openGatewayUrl($url)->assertRedirect("https://app.example.test/orders/{$order->id}?payment=success");
    openGatewayUrl($url)->assertRedirect("https://app.example.test/orders/{$order->id}?payment=success");

    expect(Payment::where('status', PaymentStatus::Paid)->count())->toBe(1)
        ->and($order->fresh()->status)->toBe(OrderStatus::Completed);
});

it('ignores callbacks with an unknown or forged token', function () {
    $order = pendingOrder();
    $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay");

    $this->get('/api/v1/payments/callback?Authority=FAKE-NOPE&Status=OK')
        ->assertRedirect('https://app.example.test/orders?payment=failed');
    $this->get('/api/v1/payments/callback')->assertRedirect('https://app.example.test/orders?payment=failed');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and(Payment::first()->status)->toBe(PaymentStatus::Pending);
});

it('does not resurrect a cancelled order when its payment arrives late', function () {
    $order = pendingOrder();
    $url = $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")->json('payment_url');
    $order->update(['status' => OrderStatus::Cancelled]);

    openGatewayUrl($url);

    expect(Payment::first()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('completes free orders immediately without a gateway', function () {
    $order = pendingOrder(total: 0);

    $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")
        ->assertOk()->assertJsonPath('payment_url', null)->assertJsonPath('order.status', 'completed');

    expect(Payment::count())->toBe(0);
});

it('refuses the fake gateway in production', function () {
    $this->app['env'] = 'production';

    expect(fn () => app(GatewayFactory::class)->named('fake'))->toThrow(LogicException::class);
});

describe('Zarinpal', function () {
    beforeEach(fn () => config([
        'payments.gateway' => 'zarinpal',
        'payments.zarinpal.merchant_id' => 'merchant-123',
        'payments.zarinpal.sandbox' => true,
    ]));

    it('registers the payment and returns the StartPay URL', function () {
        Http::fake(['*/payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A0001']])]);
        $order = pendingOrder(total: 120_000);

        $url = $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")
            ->assertOk()->json('payment_url');

        expect($url)->toBe('https://sandbox.zarinpal.com/pg/StartPay/A0001')
            ->and(Payment::first()->authority)->toBe('A0001');

        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://sandbox.zarinpal.com/pg/v4/payment/request.json'
            && $r['merchant_id'] === 'merchant-123' && $r['amount'] === 120_000 && $r['currency'] === 'IRT'
            && str_ends_with($r['callback_url'], '/api/v1/payments/callback'));
    });

    it('verifies with Zarinpal before completing the order', function () {
        Http::fake([
            '*/payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A0002']]),
            '*/payment/verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 777001]]),
        ]);
        $order = pendingOrder(total: 50_000);
        $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay");

        $this->get('/api/v1/payments/callback?Authority=A0002&Status=OK')
            ->assertRedirect("https://app.example.test/orders/{$order->id}?payment=success");

        expect(Payment::first()->reference)->toBe('777001')
            ->and($order->fresh()->status)->toBe(OrderStatus::Completed);

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/verify.json')
            && $r['authority'] === 'A0002' && $r['amount'] === 50_000);
    });

    it('does not complete the order when verification fails or the customer cancelled', function () {
        Http::fake([
            '*/payment/request.json' => Http::sequence()
                ->push(['data' => ['code' => 100, 'authority' => 'B0001']])
                ->push(['data' => ['code' => 100, 'authority' => 'B0002']]),
            '*/payment/verify.json' => Http::response(['data' => [], 'errors' => ['code' => -51]]),
        ]);
        $order = pendingOrder();
        $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay");
        $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay");

        $this->get('/api/v1/payments/callback?Authority=B0001&Status=OK')->assertRedirect("https://app.example.test/orders/{$order->id}?payment=failed");
        $this->get('/api/v1/payments/callback?Authority=B0002&Status=NOK')->assertRedirect("https://app.example.test/orders/{$order->id}?payment=failed");

        expect($order->fresh()->status)->toBe(OrderStatus::Pending)
            ->and(Payment::where('status', PaymentStatus::Failed)->count())->toBe(2);

        // a cancelled customer never even triggers a verification call
        Http::assertSentCount(3);
    });

    it('answers 502 and records a failed attempt when the gateway is unreachable', function () {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $order = pendingOrder();

        $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")->assertStatus(502);

        expect(Payment::first()->status)->toBe(PaymentStatus::Failed)
            ->and($order->fresh()->status)->toBe(OrderStatus::Pending);
    });

    it('answers 502 when Zarinpal refuses the payment', function () {
        Http::fake(['*/request.json' => Http::response(['data' => [], 'errors' => ['code' => -9]])]);
        $order = pendingOrder();

        $this->actingAs($order->user)->postJson("/api/v1/orders/{$order->id}/pay")->assertStatus(502);
    });
});
