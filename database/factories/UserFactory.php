<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    private static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function withRole(Role $role): self
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role));
    }

    public function speaker(): self
    {
        return $this->withRole(Role::Speaker);
    }

    public function reviewer(): self
    {
        return $this->withRole(Role::Reviewer);
    }

    public function admin(): self
    {
        return $this->withRole(Role::Admin);
    }
}
