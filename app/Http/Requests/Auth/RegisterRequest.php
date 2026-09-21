<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\RegistrationData;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $role = Rule::enum(Role::class);

        if (! config()->boolean('auth.allow_admin_registration')) {
            $role->except(Role::Admin);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role' => ['required', $role],
        ];
    }

    public function toData(): RegistrationData
    {
        return new RegistrationData(
            name: $this->string('name')->trim()->value(),
            email: $this->string('email')->value(),
            password: $this->string('password')->value(),
            role: Role::from($this->string('role')->value()),
        );
    }
}
