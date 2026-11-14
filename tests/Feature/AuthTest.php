<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('login'));

it('registers a user, signs them in and ignores a role sent by the client', function () {
    $response = $this->withHeaders(spa())->postJson('/api/v1/register', [
        'email' => 'new@example.test',
        'password' => 'a-Long-Passw0rd!',
        'password_confirmation' => 'a-Long-Passw0rd!',
        'role' => 'admin',
    ]);

    $response->assertCreated()->assertJsonPath('user.role', 'user');
    $this->assertAuthenticated();
    expect(User::firstWhere('email', 'new@example.test')->role)->toBe(UserRole::User);
});

it('rejects weak or unconfirmed passwords and duplicate emails', function () {
    User::factory()->create(['email' => 'taken@example.test']);

    $this->withHeaders(spa())->postJson('/api/v1/register', [
        'email' => 'taken@example.test',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
});

it('logs in with correct credentials', function () {
    $user = User::factory()->create(['password' => 'secret-Pass123']);

    $this->withHeaders(spa())->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret-Pass123',
    ])->assertOk()->assertJsonPath('user.email', $user->email);

    $this->assertAuthenticatedAs($user);
});

it('rejects wrong credentials without revealing which part was wrong', function () {
    $user = User::factory()->create();

    $this->withHeaders(spa())->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'not-the-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->assertGuest();
});

it('throttles repeated failed logins', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->withHeaders(spa())->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'nope']);
    }

    $this->withHeaders(spa())->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'nope'])
        ->assertTooManyRequests();
});

it('returns the current user and logs out', function () {
    $user = customer();

    $this->actingAs($user)->withHeaders(spa())->getJson('/api/v1/me')
        ->assertOk()->assertJsonPath('data.email', $user->email);

    $this->actingAs($user)->withHeaders(spa())->postJson('/api/v1/logout')
        ->assertOk()->assertJsonStructure(['message']);
});

it('answers 401 as JSON for unauthenticated requests', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonStructure(['message']);
});
