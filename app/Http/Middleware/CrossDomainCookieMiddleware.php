<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CrossDomainCookieMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Modifies the queued `guest_chat_token` cookie to include
     * `SameSite=None; Secure` attributes when `SESSION_SAME_SITE=none`
     * and the request is served over HTTPS.
     *
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $sameSite = strtolower(config('session.same_site', 'lax'));
        if ($sameSite !== 'none') {
            return $response;
        }

        $isSecure = $request->isSecure();
        if (! $isSecure) {
            Log::channel('daily')->warning(
                'SameSite=None requires HTTPS for guest_chat_token cookie to be sent in cross-domain iframes.',
                ['url' => $request->fullUrl()]
            );

            return $response;
        }

        foreach (Cookie::getQueuedCookies() as $cookie) {
            if ($cookie->getName() === 'guest_chat_token') {
                Cookie::queue(
                    Cookie::make(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        $cookie->getPath(),
                        $cookie->getDomain(),
                        true,                   // secure
                        $cookie->isHttpOnly(),
                        false,
                        'none'                  // sameSite
                    )
                );
            }
        }

        return $response;
    }
}
