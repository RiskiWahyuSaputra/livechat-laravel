<?php

namespace App\Http\Middleware;

use App\Services\DomainWhitelistService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DomainWhitelistMiddleware
{
    public function __construct(
        private readonly DomainWhitelistService $whitelistService
    ) {}

    /**
     * Handle an incoming request.
     *
     * - If the whitelist is empty → pass through (open mode).
     * - Reads origin from the `Origin` header; falls back to `Referer` if absent.
     * - If the origin does not match the whitelist → log to the `daily` channel
     *   (includes Origin, URL, ISO 8601 timestamp) and return a 403 JSON response.
     *
     * Requirements: 4.3, 4.4, 4.5, 4.8
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domains = $this->whitelistService->getAllowedDomains();

        // Requirement 4.3: empty whitelist → allow all
        if (empty($domains)) {
            return $next($request);
        }

        // Requirement 4.4: read Origin header, fallback to Referer
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? '';

        // Requirement 4.5: if origin does not match → 403
        if (! $this->whitelistService->isAllowed($origin)) {
            // Requirement 4.8: log the rejected request
            Log::channel('daily')->warning('DomainWhitelistMiddleware: Request blocked — domain not allowed.', [
                'origin'    => $origin ?: '(none)',
                'url'       => $request->fullUrl(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json(
                ['error' => 'Domain tidak diizinkan.'],
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
