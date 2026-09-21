<?php

declare(strict_types=1);

use App\Actions\Auth\RegisterUser;
use App\Data\RegistrationData;
use App\Enums\Role;
use Illuminate\Support\Facades\Hash;

it('creates a user with a hashed password and exactly one role', function (): void {
    $user = app(RegisterUser::class)->handle(new RegistrationData('Jo', 'jo@example.com', 'secret-pass', Role::Reviewer));

    expect($user->role())->toBe(Role::Reviewer)
        ->and($user->roles)->toHaveCount(1)
        ->and(Hash::check('secret-pass', $user->password))->toBeTrue();
});
