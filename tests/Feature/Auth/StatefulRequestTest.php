<?php

declare(strict_types=1);

use App\Enums\Role;

/*
 * Sanctum only starts a session for first-party requests. The session endpoints used to call
 * $request->session() regardless, so a call from curl/Postman — or from a host the config does
 * not know — returned 500 "Session store not set on request".
 */
it('answers a request from an unknown origin with a usable error, not a 500', function (string $uri, array $payload): void {
    $response = $this->withHeader('Origin', 'http://not-a-trusted-host.example')->postJson($uri, $payload);

    $response->assertStatus(400);
    expect($response->json('message'))
        ->toContain('first-party session cookies')
        ->toContain('SANCTUM_STATEFUL_DOMAINS')
        ->toContain(config()->array('sanctum.stateful')[0]);
})->with([
    'login' => ['/api/login', ['email' => 'reviewer@example.com', 'password' => 'password']],
    'register' => ['/api/register', [
        'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'password1',
        'password_confirmation' => 'password1', 'role' => 'speaker',
    ]],
]);

it('still signs in normally from a trusted origin', function (): void {
    $user = userWithRole(Role::Reviewer);

    // The TestCase sets a first-party Origin, as the SPA does.
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
});

it('trusts the host php artisan serve listens on out of the box', function (): void {
    expect(config()->array('sanctum.stateful'))->toContain('localhost:8000');
});
