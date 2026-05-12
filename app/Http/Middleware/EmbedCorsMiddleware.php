<?php

namespace App\Http\Middleware;

use App\Services\DomainWhitelistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmbedCorsMiddleware
{
    /**
     * Allowed HTTP methods for CORS.
     */
    private const ALLOW_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';

    /**
     * Allowed request headers for CORS.
     */
    private const ALLOW_HEADERS = 'X-CSRF-TOKEN, Content-Type, Authorization, X-Requested-With';

    /**
     * Max age (in seconds) for preflight cache.
     */
    private const MAX_AGE = '86400';

    public function __construct(
        private readonly DomainWhitelistService $whitelistService
    ) {}

    /**
     * Handle an incoming request.
     *
     * - OPTIONS preflight: return 204 immediately with all CORS headers.
     * - Other methods: pass through, then add CORS headers to the response.
     *
     * CORS origin logic:
     *   - Whitelist empty  → Access-Control-Allow-Origin: *  (no ACAC header)
     *   - Whitelist active + origin matches → echo origin + Access-Control-Allow-Credentials: true
     *   - Whitelist active + origin does NOT match → no ACAO header added (DomainWhitelistMiddleware
     *     will have already blocked the request with 403 before this point, but we guard here too)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request immediately
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            $this->addCorsHeaders($request, $response);
            $this->addPreflightHeaders($response);
            return $response;
        }

        /** @var Response $response */
        $response = $next($request);

        $this->addCorsHeaders($request, $response);

        return $response;
    }

    /**
     * Add the core CORS headers to a response based on whitelist state.
     */
    private function addCorsHeaders(Request $request, Response $response): void
    {
        $allowedDomains = $this->whitelistService->getAllowedDomains();
        $origin = $request->headers->get('Origin', '');

        if (empty($allowedDomains)) {
            // Whitelist is empty — open mode: allow all origins with wildcard
            $response->headers->set('Access-Control-Allow-Origin', '*');
            // Do NOT set Access-Control-Allow-Credentials when ACAO is * (invalid per spec)
        } elseif ($origin !== '' && $this->whitelistService->isAllowed($origin)) {
            // Whitelist active and origin matches — echo origin and allow credentials
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        // If whitelist active but origin doesn't match, no ACAO header is added.
        // DomainWhitelistMiddleware handles the 403 response in that case.

        $response->headers->set('Access-Control-Allow-Methods', self::ALLOW_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOW_HEADERS);
    }

    /**
     * Add preflight-specific headers (only for OPTIONS responses).
     */
    private function addPreflightHeaders(Response $response): void
    {
        $response->headers->set('Access-Control-Max-Age', self::MAX_AGE);
    }
}
