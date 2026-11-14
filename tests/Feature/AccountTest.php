<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('password-reset'));

it('updates the profile but never the role or e-mail', function () {
    $user = customer();

    $this->actingAs($user)->patchJson('/api/v1/me', [
        'first_name' => 'Mojtaba',
        'phone_number' => '09121234567',
        'role' => 'admin',
        'email' => 'hijack@example.test',
    ])->assertOk()->assertJsonPath('user.first_name', 'Mojtaba');

    $user->refresh();
    expect($user->role->value)->toBe('user')
        ->and($user->email)->not->toBe('hijack@example.test')
        ->and($user->phone_number)->toBe('09121234567');
});

it('keeps phone numbers unique and validates their format', function () {
    User::factory()->create(['phone_number' => '09120000000']);

    $this->actingAs(customer())->patchJson('/api/v1/me', ['phone_number' => '09120000000'])
        ->assertUnprocessable()->assertJsonValidationErrors('phone_number');
    $this->actingAs(customer())->patchJson('/api/v1/me', ['phone_number' => '123'])
        ->assertUnprocessable();
});

it('changes the password only with the correct current password', function () {
    $user = User::factory()->create(['password' => 'Old-Passw0rd!x']);

    $this->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'wrong',
        'password' => 'New-Passw0rd!x',
        'password_confirmation' => 'New-Passw0rd!x',
    ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

    $this->withHeaders(spa())->actingAs($user)->putJson('/api/v1/me/password', [
        'current_password' => 'Old-Passw0rd!x',
        'password' => 'New-Passw0rd!x',
        'password_confirmation' => 'New-Passw0rd!x',
    ])->assertOk();

    expect(Hash::check('New-Passw0rd!x', $user->fresh()->password))->toBeTrue();
});

it('answers the forgot-password request identically for known and unknown e-mails', function () {
    Notification::fake();
    $user = User::factory()->create();

    $known = $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
    $unknown = $this->postJson('/api/v1/forgot-password', ['email' => 'nobody@example.test'])->assertOk();

    expect($known->json())->toBe($unknown->json());
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('points the reset link at the Angular app', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://app.example.test']);
    $user = User::factory()->create();

    $this->postJson('/api/v1/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        return str_starts_with($url, 'https://app.example.test/reset-password?token=') && str_contains($url, 'email=');
    });
});

it('resets the password with a valid token and rejects invalid ones', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/v1/reset-password', [
        'token' => 'invalid', 'email' => $user->email,
        'password' => 'Brand-New-Passw0rd!', 'password_confirmation' => 'Brand-New-Passw0rd!',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    $this->postJson('/api/v1/reset-password', [
        'token' => $token, 'email' => $user->email,
        'password' => 'Brand-New-Passw0rd!', 'password_confirmation' => 'Brand-New-Passw0rd!',
    ])->assertOk();

    expect(Hash::check('Brand-New-Passw0rd!', $user->fresh()->password))->toBeTrue();

    // the token is single use
    $this->postJson('/api/v1/reset-password', [
        'token' => $token, 'email' => $user->email,
        'password' => 'Another-Passw0rd!x', 'password_confirmation' => 'Another-Passw0rd!x',
    ])->assertUnprocessable();
});

it('throttles password reset requests', function () {
    Notification::fake();
    $payload = ['email' => 'someone@example.test'];

    foreach (range(1, 3) as $_) {
        $this->postJson('/api/v1/forgot-password', $payload)->assertOk();
    }

    $this->postJson('/api/v1/forgot-password', $payload)->assertTooManyRequests();
});
