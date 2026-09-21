<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;

function registrationPayload(Role $role, array $overrides = []): array
{
    return [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'role' => $role->value,
        ...$overrides,
    ];
}

it('registers a user with the chosen role and signs them in', function (Role $role): void {
    $this->postJson('/api/register', registrationPayload($role))
        ->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.role', $role->value)
        ->assertJsonPath('data.permissions', array_map(fn (Permission $p) => $p->value, $role->permissions()));

    $user = User::query()->where('email', 'jane@example.com')->sole();

    expect($user->hasRole($role))->toBeTrue();
    $this->assertAuthenticatedAs($user, 'web');
})->with(Role::cases());

it('blocks admin self-registration when the flag is off', function (): void {
    config(['auth.allow_admin_registration' => false]);

    $this->postJson('/api/register', registrationPayload(Role::Admin))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    $this->postJson('/api/register', registrationPayload(Role::Reviewer))->assertCreated();
});

it('validates registration input', function (array $overrides, string $field): void {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', registrationPayload(Role::Speaker, $overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'duplicate email' => [['email' => 'taken@example.com'], 'email'],
    'unconfirmed password' => [['password_confirmation' => 'different'], 'password'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'unknown role' => [['role' => 'superuser'], 'role'],
]);
