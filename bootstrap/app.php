<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->throttleApi();

        // "guest" routes are API-only; answer with JSON instead of a redirect.
        $middleware->redirectUsersTo(fn (): never => throw new AccessDeniedHttpException('You are already signed in.'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApi);

        // Every API 404 (missing route, missing record, or a policy's denyAsNotFound) looks identical,
        // so responses never reveal model names or whether a hidden record exists.
        $exceptions->respond(fn (Response $response, Throwable $e, Request $request) => $isApi($request) && $response->getStatusCode() === 404
            ? new JsonResponse(['message' => 'Not found.'], 404)
            : $response);
    })->create();
