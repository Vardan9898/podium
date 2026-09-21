<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\CurrentUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterController extends Controller
{
    /**
     * Register and sign in.
     *
     * @unauthenticated
     */
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->handle($request->toData());

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return (new CurrentUserResource($user))->response()->setStatusCode(201);
    }
}
