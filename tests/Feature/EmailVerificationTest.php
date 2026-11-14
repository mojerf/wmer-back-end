<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config(['app.frontend_url' => 'https://app.example.test']);
    RateLimiter::clear('verification');
});

/** Registers a user and returns [user, front-end link from the e-mail]. */
function registerAndGetLink(): array
{
    Notification::fake();

    test()->withHeaders(spa())->postJson('/api/v1/register', [
        'email' => 'new@example.test',
        'password' => 'a-Long-Passw0rd!',
        'password_confirmation' => 'a-Long-Passw0rd!',
    ])->assertCreated()->assertJsonPath('user.email_verified', false);

    $user = User::firstWhere('email', 'new@example.test');
    $link = null;
    Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $n) use ($user, &$link) {
        $link = $n->toMail($user)->actionUrl;

        return true;
    });

    return [$user, $link];
}

/** Turns the front-end link into the API call the Angular app makes. */
function apiCallFor(string $link): string
{
    parse_str(parse_url($link, PHP_URL_QUERY), $q);

    return "/api/v1/email/verify/{$q['id']}/{$q['hash']}?".http_build_query(['expires' => $q['expires'], 'signature' => $q['signature']]);
}

it('sends a confirmation link to the Angular app when someone registers', function () {
    [, $link] = registerAndGetLink();

    expect($link)->toStartWith('https://app.example.test/verify-email?')
        ->toContain('id=')->toContain('hash=')->toContain('expires=')->toContain('signature=');
});

it('verifies the e-mail from the link, even in another browser, and is idempotent', function () {
    [$user, $link] = registerAndGetLink();

    $this->postJson(apiCallFor($link))->assertOk()->assertJsonStructure(['message']);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->postJson(apiCallFor($link))->assertOk();
});

it('rejects tampered, foreign and expired links', function () {
    [$user, $link] = registerAndGetLink();
    $other = User::factory()->unverified()->create();

    // altered signature
    $this->postJson(str_replace('signature=', 'signature=x', apiCallFor($link)))->assertForbidden();

    // somebody else's id with this link's signature
    $this->postJson(str_replace("/verify/{$user->id}/", "/verify/{$other->id}/", apiCallFor($link)))->assertForbidden();

    // correctly signed, but the hash does not belong to the address
    $forged = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('someone@else.test')]);
    $this->postJson($forged)->assertForbidden();

    // expired
    $this->travel(2)->hours();
    $this->postJson(apiCallFor($link))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse()->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('lets unverified users resend the link, a limited number of times, and skips verified users', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    foreach (range(1, 3) as $_) {
        $this->actingAs($user)->postJson('/api/v1/email/verification-notification')->assertOk();
    }
    $this->actingAs($user)->postJson('/api/v1/email/verification-notification')->assertTooManyRequests();
    Notification::assertSentToTimes($user, VerifyEmail::class, 3);

    RateLimiter::clear('verification');
    $verified = customer();
    $this->actingAs($verified)->postJson('/api/v1/email/verification-notification')->assertOk();
    Notification::assertNotSentTo($verified, VerifyEmail::class);
});

it('keeps unverified users from buying, paying and commenting, but not from browsing their account', function () {
    $user = User::factory()->unverified()->create();
    $product = Product::factory()->create();
    Post::factory()->create(['slug' => 'p']);
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending, 'total_price' => 1000]);

    $this->actingAs($user)->postJson('/api/v1/orders', ['product_ids' => [$product->id]])
        ->assertForbidden()->assertJsonPath('code', 'email_unverified');
    $this->actingAs($user)->postJson("/api/v1/orders/{$order->id}/pay")->assertForbidden();
    $this->actingAs($user)->postJson('/api/v1/posts/p/comments', ['body' => 'Hello'])->assertForbidden();

    $this->actingAs($user)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email_verified', false);
    $this->actingAs($user)->getJson('/api/v1/orders/mine')->assertOk();

    $user->markEmailAsVerified();
    $this->actingAs($user->fresh())->postJson('/api/v1/orders', ['product_ids' => [$product->id]])->assertCreated();
});
