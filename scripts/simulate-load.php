<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script ini harus dijalankan lewat CLI.\n");
    exit(1);
}

if (!extension_loaded('curl')) {
    fwrite(STDERR, "Extension cURL belum aktif. Aktifkan extension=curl di PHP CLI.\n");
    exit(1);
}

$options = parseArguments($argv);

if (isset($options['help'])) {
    printHelp();
    exit(0);
}

$baseUrl = rtrim((string) ($options['base-url'] ?? 'http://127.0.0.1:8000'), '/');
$mode = strtolower((string) ($options['mode'] ?? 'both'));
$users = max(1, (int) ($options['users'] ?? 10));
$messagesPerUser = max(1, (int) ($options['messages'] ?? 3));
$concurrency = max(1, (int) ($options['concurrency'] ?? 5));
$pauseMs = max(0, (int) ($options['pause-ms'] ?? 0));
$timeoutSeconds = max(5, (int) ($options['timeout'] ?? 30));
$selectedOption = $options['selected-option'] ?? null;
$token = (string) ($options['token'] ?? '');

if (!in_array($mode, ['web', 'whatsapp', 'both'], true)) {
    fwrite(STDERR, "Mode tidak valid. Gunakan: web, whatsapp, atau both.\n");
    exit(1);
}

$webPrompts = parsePromptList(
    (string) ($options['web-prompts'] ?? 'Halo|menu|Saya butuh bantuan')
);
$whatsAppPrompts = parsePromptList(
    (string) ($options['wa-prompts'] ?? 'Halo|menu|Hubungi Agent')
);

$scenarios = buildScenarios(
    mode: $mode,
    users: $users,
    messagesPerUser: $messagesPerUser,
    webPrompts: $webPrompts,
    whatsAppPrompts: $whatsAppPrompts,
    selectedOption: $selectedOption
);

$summary = runScenarios(
    scenarios: $scenarios,
    baseUrl: $baseUrl,
    concurrency: min($concurrency, count($scenarios)),
    pauseMs: $pauseMs,
    timeoutSeconds: $timeoutSeconds,
    token: $token
);

printSummary($summary, $mode, $baseUrl);

exit($summary['failed_requests'] > 0 || $summary['failed_scenarios'] > 0 ? 1 : 0);

function parseArguments(array $argv): array
{
    $parsed = [];

    for ($i = 1, $total = count($argv); $i < $total; $i++) {
        $current = $argv[$i];

        if (!str_starts_with($current, '--')) {
            continue;
        }

        $raw = substr($current, 2);

        if ($raw === 'help') {
            $parsed['help'] = true;
            continue;
        }

        if (str_contains($raw, '=')) {
            [$key, $value] = explode('=', $raw, 2);
            $parsed[$key] = $value;
            continue;
        }

        $next = $argv[$i + 1] ?? null;
        if ($next !== null && !str_starts_with($next, '--')) {
            $parsed[$raw] = $next;
            $i++;
            continue;
        }

        $parsed[$raw] = true;
    }

    return $parsed;
}

function parsePromptList(string $value): array
{
    $items = array_values(array_filter(array_map('trim', explode('|', $value)), static fn ($item) => $item !== ''));

    return $items === [] ? ['Halo'] : $items;
}

function buildScenarios(
    string $mode,
    int $users,
    int $messagesPerUser,
    array $webPrompts,
    array $whatsAppPrompts,
    string|int|null $selectedOption
): array {
    $scenarios = [];
    $seed = (int) floor(microtime(true) * 1000);

    if (in_array($mode, ['web', 'both'], true)) {
        for ($i = 1; $i <= $users; $i++) {
            $scenarios[] = [
                'key' => 'web-' . $i,
                'mode' => 'web',
                'index' => $i,
                'state' => 'pending',
                'step' => 0,
                'ready_at' => microtime(true),
                'cookie' => null,
                'conversation_id' => null,
                'selected_option' => $selectedOption,
                'messages' => expandPrompts($webPrompts, $messagesPerUser, 'Web user ' . $i),
                'request_count' => 0,
                'success_count' => 0,
                'error' => null,
            ];
        }
    }

    if (in_array($mode, ['whatsapp', 'both'], true)) {
        for ($i = 1; $i <= $users; $i++) {
            $phone = '62888' . str_pad((string) ($seed + $i), 8, '0', STR_PAD_LEFT);
            $scenarios[] = [
                'key' => 'wa-' . $i,
                'mode' => 'whatsapp',
                'index' => $i,
                'state' => 'pending',
                'step' => 0,
                'ready_at' => microtime(true),
                'phone' => $phone,
                'messages' => expandPrompts($whatsAppPrompts, $messagesPerUser, 'WA user ' . $i),
                'request_count' => 0,
                'success_count' => 0,
                'error' => null,
            ];
        }
    }

    return $scenarios;
}

function expandPrompts(array $prompts, int $messagesPerUser, string $prefix): array
{
    $expanded = [];
    $count = count($prompts);

    for ($i = 0; $i < $messagesPerUser; $i++) {
        $base = $prompts[$i % $count];
        $expanded[] = str_replace('{n}', (string) ($i + 1), $base) . ' [' . $prefix . ' #' . ($i + 1) . ']';
    }

    return $expanded;
}

function runScenarios(
    array $scenarios,
    string $baseUrl,
    int $concurrency,
    int $pauseMs,
    int $timeoutSeconds,
    string $token
): array {
    $multi = curl_multi_init();
    $activeHandles = [];
    $durations = [];
    $completedScenarios = 0;
    $requestTotal = 0;
    $requestSuccess = 0;
    $requestFailed = 0;
    $scenarioFailures = [];

    while ($completedScenarios < count($scenarios)) {
        $now = microtime(true);

        foreach ($scenarios as $index => $scenario) {
            if (count($activeHandles) >= $concurrency) {
                break;
            }

            if (($scenario['state'] ?? '') !== 'pending') {
                continue;
            }

            if (($scenario['ready_at'] ?? 0.0) > $now) {
                continue;
            }

            $request = buildRequest($scenario, $baseUrl, $token);
            $headers = [];
            $handle = curl_init();

            curl_setopt_array($handle, [
                CURLOPT_URL => $request['url'],
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_POSTFIELDS => $request['body'],
                CURLOPT_HTTPHEADER => $request['headers'],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADERFUNCTION => static function ($ch, string $headerLine) use (&$headers): int {
                    $length = strlen($headerLine);
                    $parts = explode(':', $headerLine, 2);

                    if (count($parts) === 2) {
                        $name = strtolower(trim($parts[0]));
                        $value = trim($parts[1]);
                        $headers[$name][] = $value;
                    }

                    return $length;
                },
            ]);

            curl_multi_add_handle($multi, $handle);

            $scenarios[$index]['state'] = 'running';
            $activeHandles[(int) $handle] = [
                'scenario_index' => $index,
                'handle' => $handle,
                'headers' => &$headers,
                'started_at' => microtime(true),
            ];
        }

        do {
            $multiExecStatus = curl_multi_exec($multi, $running);
        } while ($multiExecStatus === CURLM_CALL_MULTI_PERFORM);

        while ($info = curl_multi_info_read($multi)) {
            $handle = $info['handle'];
            $handleId = (int) $handle;

            if (!isset($activeHandles[$handleId])) {
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);
                continue;
            }

            $meta = $activeHandles[$handleId];
            $scenarioIndex = $meta['scenario_index'];
            $scenario = $scenarios[$scenarioIndex];
            $responseBody = (string) curl_multi_getcontent($handle);
            $httpCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($handle);
            $durationMs = (microtime(true) - $meta['started_at']) * 1000;

            $requestTotal++;
            $durations[] = $durationMs;
            $scenarios[$scenarioIndex]['request_count']++;

            $outcome = handleResponse(
                scenario: $scenario,
                httpCode: $httpCode,
                responseBody: $responseBody,
                headers: $meta['headers'],
                curlError: $curlError
            );

            if ($outcome['ok']) {
                $requestSuccess++;
                $scenarios[$scenarioIndex]['success_count']++;
                $scenarios[$scenarioIndex] = array_replace($scenarios[$scenarioIndex], $outcome['scenario']);
                $scenarios[$scenarioIndex]['state'] = $outcome['done'] ? 'done' : 'pending';
                $scenarios[$scenarioIndex]['ready_at'] = microtime(true) + ($pauseMs / 1000);
            } else {
                $requestFailed++;
                $scenarios[$scenarioIndex]['state'] = 'done';
                $scenarios[$scenarioIndex]['error'] = $outcome['error'];
                $scenarioFailures[] = [
                    'scenario' => $scenarios[$scenarioIndex]['key'],
                    'mode' => $scenarios[$scenarioIndex]['mode'],
                    'step' => $scenarios[$scenarioIndex]['step'],
                    'error' => $outcome['error'],
                    'http_code' => $httpCode,
                ];
            }

            if ($scenarios[$scenarioIndex]['state'] === 'done') {
                $completedScenarios++;
            }

            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
            unset($activeHandles[$handleId]);
        }

        if ($running > 0) {
            curl_multi_select($multi, 0.25);
            continue;
        }

        if ($completedScenarios < count($scenarios) && count($activeHandles) === 0) {
            usleep(10000);
        }
    }

    curl_multi_close($multi);

    $failedScenarioCount = count(array_filter($scenarios, static fn ($scenario) => !empty($scenario['error'])));

    return [
        'scenarios' => $scenarios,
        'durations' => $durations,
        'total_scenarios' => count($scenarios),
        'completed_scenarios' => count($scenarios) - $failedScenarioCount,
        'failed_scenarios' => $failedScenarioCount,
        'total_requests' => $requestTotal,
        'successful_requests' => $requestSuccess,
        'failed_requests' => $requestFailed,
        'scenario_failures' => array_slice($scenarioFailures, 0, 20),
    ];
}

function buildRequest(array $scenario, string $baseUrl, string $token): array
{
    if ($scenario['mode'] === 'web') {
        return buildWebRequest($scenario, $baseUrl);
    }

    return buildWhatsappRequest($scenario, $baseUrl, $token);
}

function buildWebRequest(array $scenario, string $baseUrl): array
{
    if ($scenario['step'] === 0) {
        $body = ['selected_option' => $scenario['selected_option']];
        $body = array_filter($body, static fn ($value) => $value !== null && $value !== '');

        return [
            'url' => $baseUrl . '/chat/register-anonymous',
            'headers' => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'body' => http_build_query($body),
        ];
    }

    $message = $scenario['messages'][$scenario['step'] - 1] ?? 'Halo';

    return [
        'url' => $baseUrl . '/chat/send',
        'headers' => array_values(array_filter([
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            $scenario['cookie'] ? 'Cookie: ' . $scenario['cookie'] : null,
        ])),
        'body' => http_build_query([
            'conversation_id' => $scenario['conversation_id'],
            'content' => $message,
        ]),
    ];
}

function buildWhatsappRequest(array $scenario, string $baseUrl, string $token): array
{
    $message = $scenario['messages'][$scenario['step']] ?? 'Halo';
    $messageId = 'loadtest-' . $scenario['key'] . '-' . ($scenario['step'] + 1) . '-' . uniqid('', true);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    return [
        'url' => $baseUrl . '/api/webhook/openclaw/whatsapp',
        'headers' => $headers,
        'body' => json_encode([
            'channel' => 'whatsapp',
            'fromMe' => false,
            'from' => $scenario['phone'],
            'senderName' => 'Load Test ' . $scenario['index'],
            'content' => $message,
            'messageType' => 'text',
            'messageId' => $messageId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function handleResponse(
    array $scenario,
    int $httpCode,
    string $responseBody,
    array $headers,
    string $curlError
): array {
    if ($curlError !== '') {
        return ['ok' => false, 'done' => true, 'error' => 'cURL error: ' . $curlError];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'ok' => false,
            'done' => true,
            'error' => 'HTTP ' . $httpCode . ': ' . shorten($responseBody),
        ];
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'done' => true,
            'error' => 'Respons bukan JSON valid: ' . shorten($responseBody),
        ];
    }

    if ($scenario['mode'] === 'web') {
        return handleWebResponse($scenario, $decoded, $headers);
    }

    return handleWhatsappResponse($scenario, $decoded);
}

function handleWebResponse(array $scenario, array $decoded, array $headers): array
{
    if ($scenario['step'] === 0) {
        if (!($decoded['success'] ?? false)) {
            return ['ok' => false, 'done' => true, 'error' => 'Register anonymous gagal.'];
        }

        $conversationId = $decoded['conversation']['id'] ?? null;
        if (!$conversationId) {
            return ['ok' => false, 'done' => true, 'error' => 'conversation_id tidak ditemukan saat register.'];
        }

        $cookie = extractCookieHeader($headers);
        if ($cookie === null) {
            return ['ok' => false, 'done' => true, 'error' => 'Cookie guest_chat_token tidak ditemukan.'];
        }

        $scenario['conversation_id'] = $conversationId;
        $scenario['cookie'] = $cookie;
        $scenario['step'] = 1;

        return [
            'ok' => true,
            'done' => count($scenario['messages']) === 0,
            'scenario' => $scenario,
        ];
    }

    if (!($decoded['success'] ?? false)) {
        return ['ok' => false, 'done' => true, 'error' => 'Kirim pesan web gagal.'];
    }

    $scenario['step']++;
    $done = $scenario['step'] > count($scenario['messages']);

    return [
        'ok' => true,
        'done' => $done,
        'scenario' => $scenario,
    ];
}

function handleWhatsappResponse(array $scenario, array $decoded): array
{
    $status = strtolower((string) ($decoded['status'] ?? ''));
    if (!in_array($status, ['ok', 'ignored', 'duplicate'], true)) {
        return [
            'ok' => false,
            'done' => true,
            'error' => 'Webhook WhatsApp gagal: ' . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    $scenario['step']++;
    $done = $scenario['step'] >= count($scenario['messages']);

    return [
        'ok' => true,
        'done' => $done,
        'scenario' => $scenario,
    ];
}

function extractCookieHeader(array $headers): ?string
{
    $setCookies = $headers['set-cookie'] ?? [];
    $cookies = [];

    foreach ($setCookies as $header) {
        $segments = explode(';', $header);
        $pair = trim((string) ($segments[0] ?? ''));
        if ($pair !== '') {
            $cookies[] = $pair;
        }
    }

    return $cookies === [] ? null : implode('; ', $cookies);
}

function shorten(string $value, int $max = 180): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');

    if (strlen($value) <= $max) {
        return $value;
    }

    return substr($value, 0, $max - 3) . '...';
}

function printSummary(array $summary, string $mode, string $baseUrl): void
{
    $durations = $summary['durations'];
    sort($durations);

    $avg = $durations === [] ? 0.0 : array_sum($durations) / count($durations);
    $p95 = percentile($durations, 95);
    $max = $durations === [] ? 0.0 : max($durations);

    echo "=== Livechat Load Test Summary ===\n";
    echo 'Base URL           : ' . $baseUrl . "\n";
    echo 'Mode               : ' . $mode . "\n";
    echo 'Total skenario     : ' . $summary['total_scenarios'] . "\n";
    echo 'Skenario gagal     : ' . $summary['failed_scenarios'] . "\n";
    echo 'Total request      : ' . $summary['total_requests'] . "\n";
    echo 'Request sukses     : ' . $summary['successful_requests'] . "\n";
    echo 'Request gagal      : ' . $summary['failed_requests'] . "\n";
    echo 'Avg latency        : ' . number_format($avg, 2) . " ms\n";
    echo 'P95 latency        : ' . number_format($p95, 2) . " ms\n";
    echo 'Max latency        : ' . number_format($max, 2) . " ms\n";

    if ($summary['scenario_failures'] !== []) {
        echo "\nContoh kegagalan:\n";
        foreach ($summary['scenario_failures'] as $failure) {
            echo '- ' . $failure['scenario']
                . ' [' . $failure['mode'] . ']'
                . ' step=' . $failure['step']
                . ' http=' . $failure['http_code']
                . ' error=' . $failure['error']
                . "\n";
        }
    }
}

function percentile(array $sortedValues, int $percent): float
{
    if ($sortedValues === []) {
        return 0.0;
    }

    $index = (int) ceil(($percent / 100) * count($sortedValues)) - 1;
    $index = max(0, min($index, count($sortedValues) - 1));

    return (float) $sortedValues[$index];
}

function printHelp(): void
{
    echo <<<TEXT
Usage:
  php scripts/simulate-load.php [options]

Options:
  --mode=web|whatsapp|both
  --base-url=http://127.0.0.1:8000
  --users=50
  --messages=3
  --concurrency=10
  --pause-ms=0
  --timeout=30
  --selected-option=1
  --token=BRIDGE_TOKEN
  --web-prompts="Halo|menu|Saya butuh bantuan"
  --wa-prompts="Halo|menu|Hubungi Agent"
  --help

Contoh:
  php scripts/simulate-load.php --mode=web --users=20 --messages=4 --concurrency=5
  php scripts/simulate-load.php --mode=whatsapp --users=30 --messages=2
  php scripts/simulate-load.php --mode=both --users=25 --messages=3 --base-url=http://127.0.0.1:8000

Catatan:
  - Untuk mode web, script akan memanggil /chat/register-anonymous lalu /chat/send.
  - Untuk mode whatsapp, script akan memanggil /api/webhook/openclaw/whatsapp.
  - Jika endpoint webhook memakai bearer token, isi lewat --token.
  - Placeholder {n} di prompt akan diganti nomor pesan. Contoh: "Test {n}".
TEXT;
    echo "\n";
}
