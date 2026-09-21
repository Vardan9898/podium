<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;

it('logs in with valid credentials', function (): void {
    $user = userWithRole(Role::Reviewer);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role', Role::Reviewer->value);

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects invalid credentials without revealing which field was wrong', function (): void {
    $user = userWithRole(Role::Speaker);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => trans('auth.failed')]);

    $this->assertGuest('web');
});

it('throttles login attempts per email and IP', function (): void {
    $user = userWithRole(Role::Speaker);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    }

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertTooManyRequests();
    $this->postJson('/api/login', ['email' => 'someone-else@example.com', 'password' => 'x'])->assertUnprocessable();
});

it('logs out and invalidates the session', function (): void {
    $user = userWithRole(Role::Speaker);

    $this->actingAs($user)->postJson('/api/logout')->assertNoContent();

    $this->assertGuest('web');
});

it('returns the current user with permissions but no secrets', function (): void {
    $user = userWithRole(Role::Admin);

    $this->actingAs($user)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.role', Role::Admin->value)
        ->assertJsonCount(count(Role::Admin->permissions()), 'data.permissions')
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.remember_token');
});

it('rejects anonymous access with a JSON 401', function (): void {
    $this->getJson('/api/me')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
});

it('answers already-authenticated users on guest endpoints with JSON 403, not a redirect', function (): void {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'x'])
        ->assertForbidden()
        ->assertJsonPath('message', 'You are already signed in.');
});
