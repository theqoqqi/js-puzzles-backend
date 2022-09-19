<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use function cookie;

class GenerateGuestUuid {

    const COOKIE_NAME = 'jsPuzzles_guestUuid';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        if ($request->hasCookie(self::COOKIE_NAME)) {
            return $next($request);
        }

        $uuid = Str::uuid();
        $minutes = 60 * 24 * 365 * 5; // 5 years

        $request->cookies->set(self::COOKIE_NAME, $uuid);

        $cookie = cookie(self::COOKIE_NAME, $uuid, $minutes, null, null, null, false);

        $response = $next($request);

        if ($response instanceof BinaryFileResponse) {
            $response->headers->setCookie($cookie);

            return $response;
        }

        return $response->withCookie($cookie);
    }
}
