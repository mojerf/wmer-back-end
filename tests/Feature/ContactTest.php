<?php

use App\Models\Contact;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('contact'));

it('stores a valid contact message from a guest', function () {
    $this->postJson('/api/v1/contacts', [
        'email' => 'visitor@example.test',
        'title' => 'Hello',
        'text' => 'I would like to work with you.',
        'phone_number' => '0912 345 6789',
    ])->assertCreated();

    expect(Contact::count())->toBe(1);
});

it('validates input and ignores unknown fields', function () {
    $this->postJson('/api/v1/contacts', ['email' => 'not-an-email'])
        ->assertUnprocessable()->assertJsonValidationErrors(['email', 'title', 'text']);

    $this->postJson('/api/v1/contacts', [
        'email' => 'a@example.test', 'title' => 'T', 'text' => 'B', 'id' => 999, 'created_at' => '2000-01-01',
    ])->assertCreated();

    expect(Contact::first()->id)->not->toBe(999);
});

it('rate limits spam', function () {
    $payload = ['email' => 'a@example.test', 'title' => 'T', 'text' => 'B'];

    foreach (range(1, 3) as $_) {
        $this->postJson('/api/v1/contacts', $payload)->assertCreated();
    }

    $this->postJson('/api/v1/contacts', $payload)->assertTooManyRequests();
});
