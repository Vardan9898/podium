<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\Role;

final readonly class RegistrationData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public Role $role,
    ) {}
}
