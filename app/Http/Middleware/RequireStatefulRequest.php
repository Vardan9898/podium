<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the endpoints that read or write the session.
 *
 * Sanctum only starts a session for first-party requests — ones whose Origin or Referer is
 * listed in `sanctum.stateful`. Without this guard those endpoints call $request->session()
 * on a request that has none, which surfaces as a 500 ("Session store not set on request")
 * for anyone calling the API from curl, Postman, or a host the config does not know about.
 */
final class RequireStatefulRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            return $next($request);
        }

        abort(400, sprintf(
            'This API signs in with first-party session cookies. Send the request from one of the '
            .'trusted origins (%s) with an Origin or Referer header, after calling GET /sanctum/csrf-cookie. '
            .'Add your host to SANCTUM_STATEFUL_DOMAINS, or set APP_URL to the URL you open in the browser.',
            implode(', ', config()->array('sanctum.stateful')),
        ));
    }
}
