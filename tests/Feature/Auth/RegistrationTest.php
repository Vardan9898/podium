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

it('stores the email normalised to lower case', function (): void {
    $this->postJson('/api/register', registrationPayload(Role::Speaker, ['email' => ' Jane@Example.COM ']))
        ->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com');
});

it('only allows the roles configured as self-registerable', function (): void {
    config(['auth.self_registration_roles' => [Role::Speaker->value]]);

    foreach ([Role::Admin, Role::Reviewer] as $blocked) {
        $this->postJson('/api/register', registrationPayload($blocked))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    $this->postJson('/api/register', registrationPayload(Role::Speaker))->assertCreated();
});

it('rejects every role when the configured list is empty', function (Role $role): void {
    config(['auth.self_registration_roles' => []]);

    expect(Role::selfRegisterable())->toBe([]);

    $this->postJson('/api/register', registrationPayload($role))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
})->with(Role::cases());

it('reads the roles from configuration, keeping enum order', function (): void {
    config(['auth.self_registration_roles' => ['admin', 'speaker', 'nonsense']]);

    expect(Role::selfRegisterable())->toBe([Role::Speaker, Role::Admin]);
});

it('advertises the same roles to the sign-up form', function (): void {
    config(['auth.self_registration_roles' => [Role::Speaker->value, Role::Reviewer->value]]);

    $this->getJson('/api/config')
        ->assertJsonPath('data.registerable_roles', [Role::Speaker->value, Role::Reviewer->value]);
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
