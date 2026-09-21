<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\CurrentUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class SessionController extends Controller
{
    /**
     * Log in.
     *
     * Rate limited to 5 attempts per minute per email + IP.
     *
     * @unauthenticated
     */
    public function store(LoginRequest $request): CurrentUserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        return new CurrentUserResource($request->user());
    }

    /**
     * Log out.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
