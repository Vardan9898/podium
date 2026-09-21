<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ClientConfigResource;

final class ClientConfigController extends Controller
{
    /**
     * Public client settings.
     *
     * Rating range, upload limit, tag limit and whether admins may self-register.
     *
     * @unauthenticated
     */
    public function __invoke(): ClientConfigResource
    {
        return new ClientConfigResource(null);
    }
}
