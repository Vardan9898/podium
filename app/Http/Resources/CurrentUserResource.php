<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The authenticated user, including what they are allowed to do.
 * The SPA derives all UI visibility from `permissions`, never from `role`.
 *
 * @mixin User
 */
final class CurrentUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role(),
            /** @var list<Permission> */
            'permissions' => $this->getAllPermissions()
                ->map(fn ($permission): Permission => Permission::from($permission->name))
                ->values(),
        ];
    }
}
