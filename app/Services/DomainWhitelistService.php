<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DomainWhitelistService
{
    /**
     * Get the list of allowed domains from settings, parsed per line.
     * Cached for 60 seconds with key 'embed_allowed_domains_parsed'.
     *
     * @return array<string>
     */
    public function getAllowedDomains(): array
    {
        return Cache::remember('embed_allowed_domains_parsed', 60, function () {
            try {
                $raw = Setting::get('embed_allowed_domains', '');

                if (empty($raw)) {
                    return [];
                }

                $lines = explode("\n", $raw);
                $domains = [];

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $domains[] = strtolower(rtrim($line, '/'));
                    }
                }

                return $domains;
            } catch (\Throwable $e) {
                Log::channel('daily')->error(
                    'DomainWhitelistService: Failed to read embed_allowed_domains from database. Treating whitelist as empty (fail open).',
                    ['exception' => $e->getMessage()]
                );

                return [];
            }
        });
    }

    /**
     * Extract and normalize the host from an Origin or Referer header value.
     * Returns lowercase host without trailing slash.
     *
     * @param  string  $originOrReferer
     * @return string
     */
    public function extractHost(string $originOrReferer): string
    {
        $parsed = parse_url($originOrReferer);
        $host = strtolower($parsed['host'] ?? $originOrReferer);

        return rtrim($host, '/');
    }

    /**
     * Check whether the given origin is allowed by the whitelist.
     *
     * - Returns true if the whitelist is empty (open mode).
     * - Supports wildcard subdomains: *.example.com matches sub.example.com
     *   and deep.sub.example.com.
     * - Matching is case-insensitive.
     *
     * @param  string  $origin
     * @return bool
     */
    public function isAllowed(string $origin): bool
    {
        $domains = $this->getAllowedDomains();

        if (empty($domains)) {
            return true;
        }

        $host = $this->extractHost($origin);

        foreach ($domains as $domain) {
            $domain = strtolower(rtrim($domain, '/'));

            if (str_starts_with($domain, '*.')) {
                $suffix = substr($domain, 2); // e.g. "example.com"

                if ($host === $suffix || str_ends_with($host, '.' . $suffix)) {
                    return true;
                }
            } elseif ($host === $domain) {
                return true;
            }
        }

        return false;
    }
}
