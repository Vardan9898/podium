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
    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => mb_strtolower(mb_trim($email))]);
        }
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            // Rule::in, not Rule::enum()->only(): an empty allow-list must reject every role,
            // and enum()->only([]) means "no restriction".
            'role' => ['required', 'string', Rule::in(array_column(Role::selfRegisterable(), 'value'))],
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
