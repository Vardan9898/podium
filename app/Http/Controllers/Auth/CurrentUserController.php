<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final class CurrentUserController extends Controller
{
    /**
     * The authenticated user with their role and permissions.
     */
    public function __invoke(#[CurrentUser] User $user): CurrentUserResource
    {
        return new CurrentUserResource($user);
    }
}
