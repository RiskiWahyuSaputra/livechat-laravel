<?php

namespace Tests\Feature;

use App\Services\DomainWhitelistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Cross-Domain Embed Widget.
 *
 * This file contains property-based tests and unit tests for the
 * cross-domain embed widget feature, covering CORS, domain whitelist,
 * cookie handling, and CSP behavior.
 */
class CrossDomainEmbedTest extends TestCase
{
    use RefreshDatabase;
    // =========================================================================
    // Property 1: Origin Echo
    // Validates: Requirements 2.3, 6.5
    //
    // For any domain string added to the Domain_Whitelist, isAllowed() SHALL
    // return true when that exact domain (or its https:// origin form) is
    // passed as the origin argument.
    // =========================================================================

    /**
     * Validates: Requirements 2.3, 6.5
     */
    #[Test]
    #[DataProvider('randomDomainProvider')]
    public function test_whitelisted_domain_is_allowed_by_service(string $domain): void
    {
        // Flush the parsed-domains cache so each test case starts clean.
        Cache::forget('embed_allowed_domains_parsed');

        // Stub the Setting cache so DomainWhitelistService reads our domain.
        Cache::put('setting_embed_allowed_domains', $domain, 120);

        $service = new DomainWhitelistService();

        // The service accepts both bare hostnames and full origin URLs.
        // We test with the full https:// origin form, which is what browsers send.
        $origin = 'https://' . rtrim($domain, '/');

        $this->assertTrue(
            $service->isAllowed($origin),
            "Expected isAllowed('$origin') to return true when '$domain' is whitelisted."
        );
    }

    /**
     * Generates ≥50 representative domain strings for the Origin Echo property.
     *
     * Covers:
     *  - Simple TLDs (.com, .net, .org, .io, .co.id, .id, .dev, .app, .xyz)
     *  - Numeric labels and hyphens
     *  - Long labels (up to 63 chars per RFC 1123)
     *  - Multi-level subdomains
     *  - Mixed-case (service must normalise to lowercase)
     *
     * @return array<string, array{0: string}>
     */
    public static function randomDomainProvider(): array
    {
        $domains = [
            // --- Basic TLDs ---
            'example.com',
            'example.net',
            'example.org',
            'example.io',
            'example.dev',
            'example.app',
            'example.xyz',
            'example.co.id',
            'example.id',
            'example.info',

            // --- Real-world-style domains ---
            'mycompany.com',
            'shop.mystore.net',
            'api.service.io',
            'chat.support.co.id',
            'widget.embed.app',
            'livechat.example.org',
            'helpdesk.acme.com',
            'support.brillian.id',
            'embed.widget.dev',
            'portal.enterprise.net',

            // --- Numeric labels ---
            '123.com',
            'host123.net',
            'server42.example.com',
            '99bottles.io',
            'domain007.co.id',

            // --- Hyphenated labels ---
            'my-company.com',
            'hello-world.net',
            'super-chat-widget.io',
            'embed-service.co.id',
            'live-support.app',

            // --- Multi-level subdomains ---
            'a.b.example.com',
            'deep.sub.domain.net',
            'level3.level2.level1.io',
            'chat.api.v2.example.com',
            'x.y.z.co.id',

            // --- Single-label-like (still valid hostnames) ---
            'localhost.example.com',
            'intranet.local.net',
            'dev.local.app',

            // --- Long labels (approaching 63-char limit) ---
            'abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz.com',
            'longdomainlabelfortesting1234567890abcdefghijklmnopqrstu.net',
            'verylongsubdomainlabel123456789012345678901234567890123456.io',

            // --- Additional variety ---
            'alpha.example.com',
            'beta.example.com',
            'gamma.example.com',
            'delta.example.com',
            'epsilon.example.com',
            'zeta.example.com',
            'eta.example.com',
            'theta.example.com',
            'iota.example.com',
            'kappa.example.com',
        ];

        // Build the PHPUnit data-provider array: key => [domain]
        $cases = [];
        foreach ($domains as $domain) {
            $cases[$domain] = [$domain];
        }

        return $cases;
    }

    // =========================================================================
    // Property 6: Domain Blocking
    //
    // **Validates: Requirements 4.4, 4.5, 6.4**
    //
    // For any non-empty Domain_Whitelist and for any origin string that does
    // not match any entry in that whitelist, DomainWhitelistService::isAllowed()
    // SHALL return false.
    // =========================================================================

    /**
     * @dataProvider randomNonWhitelistedDomainProvider
     */
    public function test_non_whitelisted_origin_is_blocked(string $origin): void
    {
        // Flush cache to bypass any previously cached whitelist
        Cache::flush();

        // Pre-populate the parsed whitelist cache key directly so DomainWhitelistService
        // uses a fixed known whitelist without needing a database or Setting mock.
        // The whitelist contains only 'allowed.example.com'.
        Cache::put('embed_allowed_domains_parsed', ['allowed.example.com'], 60);

        $service = new DomainWhitelistService();

        $result = $service->isAllowed($origin);

        $this->assertFalse(
            $result,
            "Expected isAllowed() to return false for origin '{$origin}' " .
            "which is not in the whitelist (only 'allowed.example.com' is allowed)."
        );
    }

    /**
     * Data provider: generates ≥50 origin strings that are NOT in the whitelist.
     *
     * The whitelist is fixed to 'allowed.example.com'.
     * All generated origins are different domains that should be blocked.
     *
     * @return array<string, array{string}>
     */
    public static function randomNonWhitelistedDomainProvider(): array
    {
        $origins = [];

        // Group 1: Simple domains with different TLDs (10 entries)
        $tlds = ['com', 'net', 'org', 'io', 'co', 'dev', 'app', 'xyz', 'info', 'biz'];
        foreach ($tlds as $tld) {
            $origins["blocked.{$tld}"] = ["https://blocked.{$tld}"];
        }

        // Group 2: Subdomains of non-whitelisted domains (10 entries)
        $subdomains = [
            'sub.notallowed.com',
            'api.otherdomain.net',
            'www.different.org',
            'chat.external.io',
            'widget.thirdparty.co',
            'embed.unauthorized.dev',
            'app.notlisted.app',
            'cdn.random.xyz',
            'static.unknown.info',
            'media.blocked.biz',
        ];
        foreach ($subdomains as $subdomain) {
            $origins[$subdomain] = ["https://{$subdomain}"];
        }

        // Group 3: Domains that look similar to the whitelisted domain but are NOT (10 entries)
        $similarDomains = [
            'https://allowed.example.org',       // different TLD
            'https://allowed.example.net',       // different TLD
            'https://allowed.example.io',        // different TLD
            'https://notallowed.example.com',    // different subdomain
            'https://allowed.examples.com',      // different base domain
            'https://allowed-example.com',       // hyphenated
            'https://allowedexample.com',        // no dot
            'https://example.com',               // base domain only (not subdomain)
            'https://sub.allowed.example.com',   // subdomain of whitelisted (not wildcard)
            'https://www.allowed.example.com',   // www subdomain of whitelisted (not wildcard)
        ];
        foreach ($similarDomains as $domain) {
            $origins[$domain] = [$domain];
        }

        // Group 4: Numeric and special format domains (5 entries)
        $numericDomains = [
            'https://192.168.1.1',
            'https://10.0.0.1',
            'https://172.16.0.1',
            'https://127.0.0.1',
            'https://0.0.0.0',
        ];
        foreach ($numericDomains as $domain) {
            $origins[$domain] = [$domain];
        }

        // Group 5: Domains with ports (5 entries)
        $domainsWithPorts = [
            'https://blocked.com:8080',
            'https://other.net:3000',
            'https://external.org:443',
            'https://thirdparty.io:8443',
            'https://unauthorized.dev:9000',
        ];
        foreach ($domainsWithPorts as $domain) {
            $origins[$domain] = [$domain];
        }

        // Group 6: HTTP origins (5 entries)
        $httpOrigins = [
            'http://blocked.com',
            'http://other.net',
            'http://external.org',
            'http://thirdparty.io',
            'http://unauthorized.dev',
        ];
        foreach ($httpOrigins as $domain) {
            $origins[$domain] = [$domain];
        }

        // Group 7: Varied domain names to reach ≥50 total (10 entries)
        $extraDomains = [
            'https://alpha.test.com',
            'https://beta.sample.net',
            'https://gamma.demo.org',
            'https://delta.mock.io',
            'https://epsilon.fake.co',
            'https://zeta.placeholder.dev',
            'https://eta.dummy.app',
            'https://theta.example2.com',
            'https://iota.notexample.com',
            'https://kappa.wrongdomain.com',
        ];
        foreach ($extraDomains as $domain) {
            $origins[$domain] = [$domain];
        }

        return $origins;
    }

    // =========================================================================
    // Property 7: Wildcard Subdomain Matching
    //
    // **Validates: Requirements 4.6**
    //
    // For any wildcard entry *.example.com in the Domain_Whitelist, and for any
    // valid subdomain string of the form sub.example.com (where sub is one or
    // more valid hostname label characters), isAllowed() SHALL return true.
    // =========================================================================

    /**
     * Generate ≥50 random subdomain strings under *.example.com.
     *
     * Each entry is a full origin URL (https://sub.example.com) so that
     * DomainWhitelistService::extractHost() can parse it correctly.
     *
     * @return array<array{string}>
     */
    public static function randomSubdomainProvider(): array
    {
        $cases = [];

        // Predefined representative subdomains
        $predefined = [
            'sub',
            'www',
            'api',
            'app',
            'mail',
            'shop',
            'blog',
            'dev',
            'staging',
            'test',
            'admin',
            'portal',
            'cdn',
            'static',
            'media',
            'assets',
            'images',
            'docs',
            'help',
            'support',
        ];

        foreach ($predefined as $sub) {
            $cases[] = ["https://{$sub}.example.com"];
        }

        // Generate additional random subdomains to reach ≥50 total
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $charsLen = strlen($chars);
        $seed = 42; // deterministic seed for reproducibility
        mt_srand($seed);

        while (count($cases) < 50) {
            // Random label length between 1 and 15 characters
            $length = mt_rand(1, 15);
            $label = '';
            for ($i = 0; $i < $length; $i++) {
                $label .= $chars[mt_rand(0, $charsLen - 1)];
            }

            // Ensure label starts with a letter (valid hostname label)
            if (!ctype_alpha($label[0])) {
                $label = 'a' . substr($label, 1);
            }

            $origin = "https://{$label}.example.com";

            // Avoid duplicates
            if (!in_array([$origin], $cases, true)) {
                $cases[] = [$origin];
            }
        }

        return $cases;
    }

    /**
     * Property 7: Wildcard Subdomain Matching
     *
     * **Validates: Requirements 4.6**
     *
     * Every subdomain of *.example.com must be allowed when the whitelist
     * contains only *.example.com.
     *
     * @dataProvider randomSubdomainProvider
     */
    public function test_wildcard_subdomain_is_allowed(string $origin): void
    {
        // Flush cache to bypass any previously cached whitelist
        Cache::flush();

        // Pre-populate the parsed whitelist cache directly so that
        // DomainWhitelistService::getAllowedDomains() returns ['*.example.com']
        // without needing a database record.
        Cache::put('embed_allowed_domains_parsed', ['*.example.com'], 60);

        $service = new DomainWhitelistService();

        $this->assertTrue(
            $service->isAllowed($origin),
            "Expected isAllowed('{$origin}') to return true when whitelist is '*.example.com', but got false."
        );
    }

    // =========================================================================
    // Property 9: Case-Insensitive Domain Matching
    //
    // **Validates: Requirements 6.7**
    //
    // For any domain entry `d` in the Domain_Whitelist, a request with Origin
    // header equal to any case variation of `d` (e.g., `Example.COM`, `EXAMPLE.com`)
    // or `d` with a trailing slash SHALL be allowed (not blocked with 403).
    // =========================================================================

    /**
     * Data provider that generates ≥50 case variations of "example.com"
     * (mixed case and trailing slash variants) to verify case-insensitive matching.
     *
     * @return array<string, array{string}>
     */
    public static function caseVariationProvider(): array
    {
        $variations = [];

        // Fully lowercase (canonical)
        $variations['all_lowercase'] = ['example.com'];

        // Fully uppercase
        $variations['all_uppercase'] = ['EXAMPLE.COM'];

        // Title case
        $variations['title_case'] = ['Example.com'];
        $variations['title_case_tld'] = ['example.Com'];
        $variations['title_case_both'] = ['Example.Com'];

        // Mixed case — individual characters uppercased
        $variations['upper_X'] = ['eXample.com'];
        $variations['upper_A'] = ['exAmple.com'];
        $variations['upper_M'] = ['exaMple.com'];
        $variations['upper_P'] = ['examPle.com'];
        $variations['upper_L'] = ['examplE.com'];
        $variations['upper_C'] = ['example.Com'];
        $variations['upper_O'] = ['example.cOm'];
        $variations['upper_M_tld'] = ['example.coM'];

        // Two characters uppercased
        $variations['upper_EX'] = ['EXample.com'];
        $variations['upper_EA'] = ['ExAmple.com'];
        $variations['upper_EM'] = ['ExaMple.com'];
        $variations['upper_EP'] = ['ExamPle.com'];
        $variations['upper_EL'] = ['ExamplE.com'];
        $variations['upper_XA'] = ['eXAmple.com'];
        $variations['upper_XM'] = ['eXaMple.com'];
        $variations['upper_XP'] = ['eXamPle.com'];
        $variations['upper_XL'] = ['eXamplE.com'];
        $variations['upper_AM'] = ['exAMple.com'];
        $variations['upper_AP'] = ['exAmPle.com'];
        $variations['upper_AL'] = ['exAmplE.com'];
        $variations['upper_MP'] = ['exaMPle.com'];
        $variations['upper_ML'] = ['exaMplE.com'];
        $variations['upper_PL'] = ['examPLE.com'];

        // Three characters uppercased
        $variations['upper_EXA'] = ['EXAmple.com'];
        $variations['upper_EXM'] = ['EXaMple.com'];
        $variations['upper_EXP'] = ['EXamPle.com'];
        $variations['upper_EXL'] = ['EXamplE.com'];
        $variations['upper_EAM'] = ['ExAMple.com'];
        $variations['upper_EAP'] = ['ExAmPle.com'];
        $variations['upper_EAL'] = ['ExAmplE.com'];
        $variations['upper_EMP'] = ['ExaMPle.com'];
        $variations['upper_EML'] = ['ExaMplE.com'];
        $variations['upper_EPL'] = ['ExamPLE.com'];

        // With trailing slash (raw host with slash — no scheme)
        $variations['trailing_slash_lower'] = ['example.com/'];
        $variations['trailing_slash_upper'] = ['EXAMPLE.COM/'];
        $variations['trailing_slash_title'] = ['Example.com/'];
        $variations['trailing_slash_mixed1'] = ['eXample.com/'];
        $variations['trailing_slash_mixed2'] = ['exAmple.com/'];
        $variations['trailing_slash_mixed3'] = ['exaMple.com/'];
        $variations['trailing_slash_mixed4'] = ['examPle.com/'];
        $variations['trailing_slash_mixed5'] = ['examplE.com/'];
        $variations['trailing_slash_mixed6'] = ['EXAmple.com/'];
        $variations['trailing_slash_mixed7'] = ['ExAmPle.com/'];
        $variations['trailing_slash_mixed8'] = ['EXAMPLE.com/'];
        $variations['trailing_slash_mixed9'] = ['example.COM/'];
        $variations['trailing_slash_mixed10'] = ['Example.COM/'];

        // With https:// scheme (extractHost strips scheme)
        $variations['https_lower'] = ['https://example.com'];
        $variations['https_upper'] = ['https://EXAMPLE.COM'];
        $variations['https_title'] = ['https://Example.com'];
        $variations['https_mixed1'] = ['https://eXample.com'];
        $variations['https_mixed2'] = ['https://EXAMPLE.com'];
        $variations['https_mixed3'] = ['https://example.COM'];
        $variations['https_mixed4'] = ['https://Example.COM'];
        $variations['https_mixed5'] = ['https://EXAmple.com'];
        $variations['https_mixed6'] = ['https://ExAmPle.com'];

        return $variations;
    }

    /**
     * Property 9: Case-Insensitive Domain Matching
     *
     * For any domain entry `d` in the Domain_Whitelist, a request with Origin
     * header equal to any case variation of `d` (e.g., `Example.COM`, `EXAMPLE.com`)
     * or `d` with a trailing slash SHALL be allowed (not blocked with 403).
     *
     * **Validates: Requirements 6.7**
     *
     * @dataProvider caseVariationProvider
     */
    public function test_domain_matching_is_case_insensitive(string $origin): void
    {
        // Flush cache to bypass any previously cached whitelist
        Cache::flush();

        // Pre-populate the Setting cache key so DomainWhitelistService reads 'example.com'
        // without hitting the database. Setting::get() checks Cache::get('setting_<key>')
        // before querying the DB, so this bypasses the DB entirely.
        Cache::put('setting_embed_allowed_domains', 'example.com');

        $service = new DomainWhitelistService();

        $result = $service->isAllowed($origin);

        $this->assertTrue(
            $result,
            "Expected isAllowed('{$origin}') to return true (case-insensitive match for 'example.com'), but got false."
        );
    }

    // =========================================================================
    // Unit Tests for DomainWhitelistMiddleware
    //
    // Requirements: 4.3, 4.4, 4.5, 4.6, 6.4, 6.7
    // =========================================================================

    /**
     * Register test routes for middleware unit tests and flush the whitelist cache.
     * Runs before each test method in this class.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Register a route protected by embed.whitelist for DomainWhitelistMiddleware tests
        Route::middleware(['embed.whitelist'])
            ->get('/test-whitelist', fn () => response('ok', 200));

        // Register routes protected by embed.cors for EmbedCorsMiddleware tests
        Route::middleware(['embed.cors'])
            ->get('/test-cors', fn () => response('ok', 200));

        Route::middleware(['embed.cors'])
            ->options('/test-cors', fn () => response('', 204));
    }

    /**
     * Helper: flush the parsed-domains cache before each middleware unit test.
     */
    private function flushWhitelistCache(): void
    {
        Cache::forget('embed_allowed_domains_parsed');
    }

    /**
     * Requirement 4.5, 6.4
     *
     * An origin that is NOT in the whitelist must receive a 403 JSON response
     * with body {"error": "Domain tidak diizinkan."}.
     */
    public function test_blocked_origin_returns_403(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        $response = $this->withHeaders(['Origin' => 'https://blocked.com'])
            ->get('/test-whitelist');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Domain tidak diizinkan.']);
    }

    /**
     * Requirement 4.4, 4.5
     *
     * An origin that IS in the whitelist must pass through and receive a 200.
     */
    public function test_allowed_origin_passes_through(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        $response = $this->withHeaders(['Origin' => 'https://example.com'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    /**
     * Requirement 4.3
     *
     * When the whitelist is empty, every origin must pass through (open mode).
     */
    public function test_empty_whitelist_allows_all_origins(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->withHeaders(['Origin' => 'https://any-random-domain.com'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    /**
     * Requirement 4.4
     *
     * When the Origin header is absent but a Referer header is present,
     * the middleware must validate using the Referer value.
     * A Referer pointing to a whitelisted domain must pass through.
     */
    public function test_missing_origin_header_uses_referer_fallback(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        // No Origin header — only Referer
        $response = $this->withHeaders(['Referer' => 'https://example.com/some/page'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    /**
     * Requirement 4.6
     *
     * A wildcard entry *.example.com must match any subdomain such as
     * sub.example.com.
     */
    public function test_wildcard_subdomain_allowed(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['*.example.com'], 60);

        $response = $this->withHeaders(['Origin' => 'https://sub.example.com'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    /**
     * Requirement 6.7
     *
     * Domain matching must be case-insensitive: an Origin of "Example.COM"
     * must match the whitelist entry "example.com".
     */
    public function test_domain_matching_case_insensitive(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        $response = $this->withHeaders(['Origin' => 'https://Example.COM'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    /**
     * Requirement 6.7
     *
     * Domain matching must ignore a trailing slash: an Origin of "example.com/"
     * must match the whitelist entry "example.com".
     */
    public function test_domain_matching_ignores_trailing_slash(): void
    {
        $this->flushWhitelistCache();
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        // The Origin header value with a trailing slash (bare host form)
        $response = $this->withHeaders(['Origin' => 'example.com/'])
            ->get('/test-whitelist');

        $response->assertStatus(200);
    }

    // =========================================================================
    // Unit Tests for EmbedCorsMiddleware
    //
    // Requirements: 2.3, 2.4, 2.5, 2.6, 6.5, 6.6
    // =========================================================================

    /**
     * Requirement 2.4, 6.5
     *
     * When the Domain_Whitelist is empty (open mode), the response MUST include
     * Access-Control-Allow-Origin: * so that any browser origin is accepted.
     */
    public function test_widget_route_returns_cors_headers_when_whitelist_empty(): void
    {
        // Empty whitelist → open mode
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->get('/test-cors');

        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    /**
     * Requirement 2.6, 6.6
     *
     * A preflight OPTIONS request must receive HTTP 204 and all required
     * CORS headers: Access-Control-Allow-Origin, Access-Control-Allow-Methods,
     * Access-Control-Allow-Headers, and Access-Control-Max-Age.
     */
    public function test_preflight_options_returns_204(): void
    {
        // Whitelist empty so ACAO is set to *
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->options('/test-cors');

        $response->assertStatus(204);
        $response->assertHeaderMissing('Content-Type'); // 204 has no body
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Origin header must be present on preflight response.'
        );
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Allow-Methods'),
            'Access-Control-Allow-Methods header must be present on preflight response.'
        );
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Allow-Headers'),
            'Access-Control-Allow-Headers header must be present on preflight response.'
        );
        $this->assertNotEmpty(
            $response->headers->get('Access-Control-Max-Age'),
            'Access-Control-Max-Age header must be present on preflight response.'
        );
    }

    /**
     * Requirement 2.3, 6.5
     *
     * When the request Origin is in the whitelist, the response MUST echo
     * that exact origin value in Access-Control-Allow-Origin (not a wildcard).
     */
    public function test_allowed_origin_echoed_in_acao(): void
    {
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        $response = $this->withHeaders(['Origin' => 'https://example.com'])
            ->get('/test-cors');

        $response->assertHeader('Access-Control-Allow-Origin', 'https://example.com');
    }

    /**
     * Requirement 2.5
     *
     * When Access-Control-Allow-Origin is the wildcard *, the response MUST NOT
     * include Access-Control-Allow-Credentials: true, because that combination
     * is invalid per the CORS specification.
     */
    public function test_credentials_header_absent_when_acao_is_wildcard(): void
    {
        // Empty whitelist → ACAO: *
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->get('/test-cors');

        $response->assertHeader('Access-Control-Allow-Origin', '*');

        // ACAC must NOT be 'true' when ACAO is *
        $acac = $response->headers->get('Access-Control-Allow-Credentials');
        $this->assertNotSame(
            'true',
            $acac,
            'Access-Control-Allow-Credentials must not be "true" when Access-Control-Allow-Origin is "*".'
        );
    }

    // =========================================================================
    // Unit Tests for EmbedDocsController / embed-docs page
    //
    // Requirements: 5.1, 5.6
    // =========================================================================

    /**
     * Requirement 5.1
     *
     * The /embed-docs page must be publicly accessible without authentication
     * and return HTTP 200.
     */
    public function test_embed_docs_page_accessible_without_auth(): void
    {
        // Ensure whitelist is empty so no contact info section is shown
        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', [], 60);

        // No auth — public access
        $response = $this->get('/embed-docs');

        $response->assertStatus(200);
    }

    /**
     * Requirement 5.6
     *
     * When the Domain_Whitelist is active (contains one or more domains),
     * the /embed-docs page must display at least one actionable contact method
     * so that embedders can request domain registration.
     */
    public function test_embed_docs_shows_contact_info_when_whitelist_active(): void
    {
        // Activate the whitelist with one domain
        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', ['example.com'], 60);

        $response = $this->get('/embed-docs');

        $response->assertStatus(200);

        // The page must contain the contact info section
        $response->assertSee('id="contact-info"', false);
    }

    /**
     * Requirement 5.6 (inverse)
     *
     * When the Domain_Whitelist is empty, the /embed-docs page must NOT
     * display the contact info section (no registration needed).
     */
    public function test_embed_docs_no_contact_info_when_whitelist_empty(): void
    {
        // Empty whitelist → open mode, no contact info needed
        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->get('/embed-docs');

        $response->assertStatus(200);

        // The contact info section must NOT be present
        $response->assertDontSee('id="contact-info"', false);
    }

    // =========================================================================
    // Task 5.2 — Unit Tests for CrossDomainCookieMiddleware
    //
    // Requirements: 1.1, 1.2, 1.3, 1.5, 6.3
    // =========================================================================

    /**
     * Requirement 1.1, 6.3
     *
     * When SESSION_SAME_SITE=none and the request is served over HTTPS,
     * the guest_chat_token cookie must be set with SameSite=None and Secure attributes.
     *
     * Tests the middleware directly without going through the full HTTP stack.
     */
    public function test_guest_token_cookie_has_samesite_none_when_configured(): void
    {
        // Configure SameSite=none
        config(['session.same_site' => 'none']);

        // Create a mock HTTPS request
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTPS' => 'on']);

        // Queue a guest_chat_token cookie before the middleware runs
        \Illuminate\Support\Facades\Cookie::queue(
            \Illuminate\Support\Facades\Cookie::make('guest_chat_token', 'test-value', 35)
        );

        // Instantiate and run the middleware directly
        $middleware = new \App\Http\Middleware\CrossDomainCookieMiddleware();
        $response = $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        // The middleware should have re-queued the cookie with SameSite=None; Secure
        $queuedCookies = \Illuminate\Support\Facades\Cookie::getQueuedCookies();

        // Find the guest_chat_token cookie with SameSite=none
        $guestCookie = null;
        foreach ($queuedCookies as $cookie) {
            if ($cookie->getName() === 'guest_chat_token' && strtolower((string) $cookie->getSameSite()) === 'none') {
                $guestCookie = $cookie;
                break;
            }
        }

        $this->assertNotNull($guestCookie, 'guest_chat_token cookie with SameSite=None must be queued after middleware runs.');
        $this->assertTrue($guestCookie->isSecure(), 'guest_chat_token cookie must have the Secure attribute when SameSite=None.');
        $this->assertSame('none', strtolower((string) $guestCookie->getSameSite()), 'guest_chat_token cookie must have SameSite=None when configured.');
    }

    /**
     * Requirement 1.2
     *
     * When SESSION_SAME_SITE is NOT 'none' (e.g., 'lax'), the middleware must
     * NOT modify the cookie — it should pass through unchanged.
     */
    public function test_cookie_not_modified_when_samesite_not_none(): void
    {
        // Configure SameSite=lax (not none)
        config(['session.same_site' => 'lax']);

        // Create a mock HTTPS request
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['HTTPS' => 'on']);

        // Queue a guest_chat_token cookie
        \Illuminate\Support\Facades\Cookie::queue(
            \Illuminate\Support\Facades\Cookie::make('guest_chat_token', 'test-value', 35)
        );

        // Count queued cookies before middleware
        $beforeCount = count(\Illuminate\Support\Facades\Cookie::getQueuedCookies());

        // Instantiate and run the middleware directly
        $middleware = new \App\Http\Middleware\CrossDomainCookieMiddleware();
        $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        // Count queued cookies after middleware — should be the same (no re-queuing)
        $afterCount = count(\Illuminate\Support\Facades\Cookie::getQueuedCookies());

        // Middleware should NOT have added a new cookie (no re-queuing when SameSite != none)
        $this->assertSame(
            $beforeCount,
            $afterCount,
            'Middleware must NOT re-queue the cookie when SESSION_SAME_SITE is not "none".'
        );

        // Verify no cookie with SameSite=none was added
        foreach (\Illuminate\Support\Facades\Cookie::getQueuedCookies() as $cookie) {
            if ($cookie->getName() === 'guest_chat_token') {
                $this->assertNotSame(
                    'none',
                    strtolower((string) $cookie->getSameSite()),
                    'Cookie SameSite must NOT be "none" when SESSION_SAME_SITE is "lax".'
                );
            }
        }
    }

    /**
     * Requirement 1.5
     *
     * When SESSION_SAME_SITE=none but the request is NOT over HTTPS,
     * the server must log a warning to the daily channel.
     */
    public function test_warning_logged_when_samesite_none_without_https(): void
    {
        // Configure SameSite=none
        config(['session.same_site' => 'none']);

        // Create a mock HTTP (non-HTTPS) request
        $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
            'HTTPS' => 'off',
            'SERVER_PORT' => '80',
        ]);

        // Queue a guest_chat_token cookie
        \Illuminate\Support\Facades\Cookie::queue(
            \Illuminate\Support\Facades\Cookie::make('guest_chat_token', 'test-value', 35)
        );

        // Capture log calls using a spy
        $logChannel = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->with('daily')
            ->once()
            ->andReturn($logChannel);

        // Instantiate and run the middleware directly
        $middleware = new \App\Http\Middleware\CrossDomainCookieMiddleware();
        $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        // Verify that a warning was logged
        $logChannel->shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message) {
                return str_contains($message, 'SameSite=None') && str_contains($message, 'HTTPS');
            });
    }

    // =========================================================================
    // Task 8.2 — Unit Tests for CSP Dynamic frame-ancestors
    //
    // Requirements: 6.1, 6.2
    // =========================================================================

    /**
     * Requirement 6.1
     *
     * When the Domain_Whitelist is empty, the Content-Security-Policy header
     * on GET /chat-widget must contain "frame-ancestors *".
     */
    public function test_csp_frame_ancestors_wildcard_when_whitelist_empty(): void
    {
        // Empty whitelist → open mode
        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', [], 60);

        $response = $this->get('/chat-widget');

        $response->assertStatus(200);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'Content-Security-Policy header must be present.');
        $this->assertStringContainsString(
            'frame-ancestors *',
            $csp,
            'CSP must contain "frame-ancestors *" when whitelist is empty.'
        );
    }

    /**
     * Requirement 6.2
     *
     * When the Domain_Whitelist contains one or more domains, the
     * Content-Security-Policy header on GET /chat-widget must contain
     * a "frame-ancestors" directive listing those domains (not a wildcard).
     */
    public function test_csp_frame_ancestors_lists_domains_when_whitelist_set(): void
    {
        $domains = ['example.com', 'mysite.org'];

        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', $domains, 60);

        // Send Origin header matching a whitelisted domain so DomainWhitelistMiddleware passes
        $response = $this->withHeaders(['Origin' => 'https://example.com'])
            ->get('/chat-widget');

        $response->assertStatus(200);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'Content-Security-Policy header must be present.');

        // Must NOT contain wildcard
        $this->assertStringNotContainsString(
            'frame-ancestors *',
            $csp,
            'CSP must NOT contain "frame-ancestors *" when whitelist has domains.'
        );

        // Must contain each domain
        foreach ($domains as $domain) {
            $this->assertStringContainsString(
                $domain,
                $csp,
                "CSP frame-ancestors must include domain '{$domain}'."
            );
        }

        // Must contain the frame-ancestors directive
        $this->assertStringContainsString(
            'frame-ancestors',
            $csp,
            'CSP must contain a frame-ancestors directive.'
        );
    }

    // =========================================================================
    // Task 8.3 — Property Test: CSP frame-ancestors Reflects Whitelist
    //
    // Property 8: CSP frame-ancestors Reflects Whitelist
    // **Validates: Requirements 6.2**
    //
    // For any non-empty list of domains in the Domain_Whitelist, the
    // Content-Security-Policy header on GET /chat-widget SHALL contain a
    // frame-ancestors directive that includes each domain and does NOT
    // contain the wildcard *.
    // =========================================================================

    /**
     * Property 8: CSP frame-ancestors Reflects Whitelist
     *
     * **Validates: Requirements 6.2**
     *
     * @dataProvider randomDomainListProvider
     */
    public function test_csp_reflects_whitelist_domains(array $domains): void
    {
        // Pre-populate the parsed whitelist cache with the given domain list
        Cache::forget('embed_allowed_domains_parsed');
        Cache::put('embed_allowed_domains_parsed', $domains, 60);

        // Send Origin header matching the first whitelisted domain so DomainWhitelistMiddleware passes
        $firstDomain = $domains[0];
        $origin = str_starts_with($firstDomain, 'http') ? $firstDomain : 'https://' . $firstDomain;

        $response = $this->withHeaders(['Origin' => $origin])
            ->get('/chat-widget');

        $response->assertStatus(200);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp, 'Content-Security-Policy header must be present.');

        // Must NOT contain wildcard when whitelist is non-empty
        $this->assertStringNotContainsString(
            'frame-ancestors *',
            $csp,
            'CSP must NOT contain "frame-ancestors *" when whitelist has domains. Domains: ' . implode(', ', $domains)
        );

        // Every domain in the whitelist must appear in the CSP frame-ancestors value
        foreach ($domains as $domain) {
            $this->assertStringContainsString(
                $domain,
                $csp,
                "CSP frame-ancestors must include domain '{$domain}'. Full CSP: {$csp}"
            );
        }
    }

    /**
     * Data provider for Property 8: generates ≥50 non-empty domain lists.
     *
     * Each entry is an array of one or more domain strings.
     * Covers single domains, multiple domains, wildcard entries, and mixed lists.
     *
     * @return array<string, array{0: array<string>}>
     */
    public static function randomDomainListProvider(): array
    {
        $cases = [];

        // Single-domain lists (20 entries)
        $singleDomains = [
            'example.com',
            'mysite.org',
            'shop.example.net',
            'api.service.io',
            'chat.support.co.id',
            'widget.embed.app',
            'livechat.example.org',
            'helpdesk.acme.com',
            'support.brillian.id',
            'embed.widget.dev',
            'portal.enterprise.net',
            'my-company.com',
            'hello-world.net',
            'super-chat-widget.io',
            'embed-service.co.id',
            'live-support.app',
            'a.b.example.com',
            'deep.sub.domain.net',
            'level3.level2.level1.io',
            'chat.api.v2.example.com',
        ];
        foreach ($singleDomains as $domain) {
            $cases["single_{$domain}"] = [[$domain]];
        }

        // Two-domain lists (15 entries)
        $twoDomainPairs = [
            ['example.com', 'mysite.org'],
            ['shop.example.net', 'api.service.io'],
            ['chat.support.co.id', 'widget.embed.app'],
            ['livechat.example.org', 'helpdesk.acme.com'],
            ['support.brillian.id', 'embed.widget.dev'],
            ['portal.enterprise.net', 'my-company.com'],
            ['hello-world.net', 'super-chat-widget.io'],
            ['embed-service.co.id', 'live-support.app'],
            ['a.b.example.com', 'deep.sub.domain.net'],
            ['level3.level2.level1.io', 'chat.api.v2.example.com'],
            ['alpha.example.com', 'beta.example.com'],
            ['gamma.example.com', 'delta.example.com'],
            ['epsilon.example.com', 'zeta.example.com'],
            ['eta.example.com', 'theta.example.com'],
            ['iota.example.com', 'kappa.example.com'],
        ];
        foreach ($twoDomainPairs as $index => $pair) {
            $cases["pair_{$index}"] = [$pair];
        }

        // Three-domain lists (10 entries)
        $threeDomainGroups = [
            ['example.com', 'mysite.org', 'shop.example.net'],
            ['api.service.io', 'chat.support.co.id', 'widget.embed.app'],
            ['livechat.example.org', 'helpdesk.acme.com', 'support.brillian.id'],
            ['embed.widget.dev', 'portal.enterprise.net', 'my-company.com'],
            ['hello-world.net', 'super-chat-widget.io', 'embed-service.co.id'],
            ['live-support.app', 'a.b.example.com', 'deep.sub.domain.net'],
            ['alpha.example.com', 'beta.example.com', 'gamma.example.com'],
            ['delta.example.com', 'epsilon.example.com', 'zeta.example.com'],
            ['eta.example.com', 'theta.example.com', 'iota.example.com'],
            ['kappa.example.com', 'lambda.example.com', 'mu.example.com'],
        ];
        foreach ($threeDomainGroups as $index => $group) {
            $cases["triple_{$index}"] = [$group];
        }

        // Five-domain lists (5 entries)
        $fiveDomainGroups = [
            ['example.com', 'mysite.org', 'shop.example.net', 'api.service.io', 'chat.support.co.id'],
            ['widget.embed.app', 'livechat.example.org', 'helpdesk.acme.com', 'support.brillian.id', 'embed.widget.dev'],
            ['portal.enterprise.net', 'my-company.com', 'hello-world.net', 'super-chat-widget.io', 'embed-service.co.id'],
            ['alpha.example.com', 'beta.example.com', 'gamma.example.com', 'delta.example.com', 'epsilon.example.com'],
            ['zeta.example.com', 'eta.example.com', 'theta.example.com', 'iota.example.com', 'kappa.example.com'],
        ];
        foreach ($fiveDomainGroups as $index => $group) {
            $cases["five_{$index}"] = [$group];
        }

        return $cases;
    }
}
