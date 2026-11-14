<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Feature tests boot the framework against an in-memory SQLite database (see phpunit.xml)
| and start every test with a fresh schema.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/** A signed-in administrator. */
function admin(): User
{
    return User::factory()->admin()->create();
}

/** A signed-in regular user. */
function customer(): User
{
    return User::factory()->create(['role' => UserRole::User]);
}

/** Stateful (cookie/session) requests are recognised by their Origin, exactly like the SPA's. */
function spa(): array
{
    return ['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'];
}
