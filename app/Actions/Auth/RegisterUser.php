<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\RegistrationData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterUser
{
    public function handle(RegistrationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
            ]);

            $user->assignRole($data->role);

            return $user;
        });
    }
}
