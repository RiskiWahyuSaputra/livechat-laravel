<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class OpenClawWhatsappService
{
    protected string $cliPath;
    protected string $nodePath;
    protected string $scriptPath;
    protected string $gatewayUrl;
    protected string $gatewayOrigin;
    protected string $gatewayToken;
    protected string $stateDir;
    protected string $configPath;
    protected string $channel;
    protected string $account;
    protected string $bridgeToken;
    protected string $publicBaseUrl;
    protected bool $enabled;

    public function __construct()
    {
        $this->cliPath = trim((string) Setting::get('openclaw_cli_path', env('OPENCLAW_CLI_PATH', 'openclaw')));
        $this->nodePath = trim((string) Setting::get('openclaw_node_path', env('OPENCLAW_NODE_PATH', 'node')));
        $this->scriptPath = trim((string) Setting::get('openclaw_script_path', env('OPENCLAW_SCRIPT_PATH', '')));
        $this->gatewayUrl = trim((string) env('OPENCLAW_GATEWAY_URL', 'ws://127.0.0.1:18789'));
        $this->gatewayOrigin = trim((string) env('OPENCLAW_GATEWAY_ORIGIN', ''));
        $this->gatewayToken = trim((string) env('OPENCLAW_GATEWAY_TOKEN', ''));
        $this->stateDir = trim((string) env('OPENCLAW_STATE_DIR', ''));
        $this->configPath = trim((string) env('OPENCLAW_CONFIG_PATH', ''));
        $this->channel = trim((string) Setting::get('openclaw_whatsapp_channel', env('OPENCLAW_WHATSAPP_CHANNEL', 'whatsapp')));
        $this->account = trim((string) Setting::get('openclaw_whatsapp_account', env('OPENCLAW_WHATSAPP_ACCOUNT', '')));
        $this->bridgeToken = trim((string) Setting::get('openclaw_bridge_token', env('OPENCLAW_BRIDGE_TOKEN', '')));
        $this->publicBaseUrl = rtrim((string) Setting::get('openclaw_public_base_url', env('OPENCLAW_PUBLIC_BASE_URL', env('ASSET_URL', env('APP_URL', '')))), '/');
        $this->enabled = filter_var(Setting::get('openclaw_whatsapp_enabled', env('OPENCLAW_WHATSAPP_ENABLED', true)), FILTER_VALIDATE_BOOL);

        if ($this->gatewayOrigin === '') {
            $this->gatewayOrigin = $this->resolveGatewayOrigin($this->gatewayUrl);
        }

        if ($this->gatewayToken === '') {
            $this->gatewayToken = $this->readGatewayTokenFromConfig();
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getBridgeToken(): string
    {
        return $this->bridgeToken;
    }

    public function sendText(User $user, string $text, array $buttons = []): bool
    {
        return $this->runMessageCommand($user, $this->normalizeTextForWhatsapp($text), null, $buttons);
    }

    public function sendMedia(User $user, string $mediaUrl, string $caption = '', array $buttons = []): bool
    {
        return $this->runMessageCommand($user, $this->normalizeTextForWhatsapp($caption), $mediaUrl, $buttons);
    }

    public function markAsRead(User $user, string $messageId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $target = $this->normalizeTarget($user->contact);
        if ($target === null) {
            return false;
        }

        $command = [
            $this->nodePath !== '' ? $this->nodePath : 'node',
            base_path('scripts/openclaw-gateway-send.mjs'),
            '--method',
            'whatsapp.read',
            '--channel',
            $this->channel,
            '--target',
            $target,
            '--message-id',
            $messageId,
        ];

        if ($this->account !== '') {
            $command[] = '--account';
            $command[] = $this->account;
        }

        try {
            $process = new Process($command, base_path(), $this->buildProcessEnv(), null, 10);
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function runMessageCommand(User $user, string $text = '', ?string $mediaUrl = null, array $buttons = []): bool
    {
        if (!$this->isEnabled()) {
            Log::info('OpenClaw WhatsApp bridge nonaktif. Skip kirim outbound message.');
            return false;
        }

        $target = $this->normalizeTarget($user->contact);
        if ($target === null) {
            Log::warning('OpenClaw WhatsApp target tidak valid.', ['user_id' => $user->id, 'contact' => $user->contact]);
            return false;
        }

        $resolvedMedia = $this->resolveMediaReference($mediaUrl);
        if (trim((string) $mediaUrl) !== '' && !$resolvedMedia['url'] && !$resolvedMedia['file']) {
            Log::warning('Media WhatsApp dilewati karena tidak ada URL publik yang bisa dipakai gateway.', [
                'user_id' => $user->id,
                'original_media_url' => $mediaUrl,
                'suggested_env' => 'OPENCLAW_PUBLIC_BASE_URL',
            ]);
        }

        $command = $this->buildGatewaySendCommand(
            $target,
            $text,
            $resolvedMedia['url'],
            $buttons,
            $resolvedMedia['file'],
        );

        try {
            $process = new Process($command, base_path(), $this->buildProcessEnv(), null, 30);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('OpenClaw outbound WhatsApp gagal.', [
                    'command' => $command,
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('OpenClaw outbound WhatsApp exception: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizeTarget(?string $contact): ?string
    {
        if (!$contact) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $contact);
        if (!$normalized) {
            return null;
        }

        if (!str_starts_with($normalized, '+') && !str_starts_with($normalized, '0')) {
            return '+' . $normalized;
        }

        return $normalized;
    }

    private function buildBaseCommand(string $target): array
    {
        $base = [];

        if ($this->nodePath !== '' && $this->scriptPath !== '') {
            $base = [
                $this->nodePath,
                $this->scriptPath,
            ];
        } else {
            $base = [
                $this->cliPath,
            ];
        }

        return array_merge($base, [
            'message',
            'send',
            '--channel',
            $this->channel,
            '--target',
            $target,
        ]);
    }

    private function buildGatewaySendCommand(string $target, string $text = '', ?string $mediaUrl = null, array $buttons = [], ?string $mediaFile = null): array
    {
        $command = [
            $this->nodePath !== '' ? $this->nodePath : 'node',
            base_path('scripts/openclaw-gateway-send.mjs'),
            '--channel',
            $this->channel,
            '--target',
            $target,
        ];

        if ($this->account !== '') {
            $command[] = '--account';
            $command[] = $this->account;
        }

        if (trim($text) !== '') {
            $command[] = '--message';
            $command[] = trim($text);
        }

        if ($mediaUrl) {
            $command[] = '--media';
            $command[] = $mediaUrl;
        }

        if ($mediaFile) {
            $command[] = '--media-file';
            $command[] = $mediaFile;
        }

        if (!empty($buttons)) {
            $command[] = '--buttons';
            $command[] = json_encode($buttons);
        }

        return $command;
    }

    private function buildProcessEnv(): array
    {
        $home = $this->resolveHomeDirectory();
        $currentEnv = getenv();

        if (!is_array($currentEnv)) {
            $currentEnv = [];
        }

        $env = array_filter([
            'APPDATA' => env('APPDATA'),
            'COMSPEC' => getenv('COMSPEC') ?: env('COMSPEC'),
            'HOME' => $home,
            'HOMEDRIVE' => env('HOMEDRIVE'),
            'HOMEPATH' => env('HOMEPATH'),
            'LOCALAPPDATA' => env('LOCALAPPDATA'),
            'OPENCLAW_CONFIG_PATH' => $this->resolveConfigPath(),
            'OPENCLAW_GATEWAY_ORIGIN' => $this->gatewayOrigin,
            'OPENCLAW_GATEWAY_TOKEN' => $this->gatewayToken,
            'OPENCLAW_GATEWAY_URL' => $this->gatewayUrl,
            'OPENCLAW_STATE_DIR' => $this->resolveStateDir(),
            'OPENCLAW_SCRIPT_PATH' => $this->scriptPath,
            'PATH' => env('PATH'),
            'PATHEXT' => getenv('PATHEXT') ?: env('PATHEXT'),
            'SYSTEMROOT' => getenv('SYSTEMROOT') ?: getenv('SystemRoot') ?: env('SYSTEMROOT'),
            'SystemRoot' => getenv('SystemRoot') ?: getenv('SYSTEMROOT') ?: env('SystemRoot'),
            'TEMP' => getenv('TEMP') ?: env('TEMP'),
            'TMP' => getenv('TMP') ?: env('TMP'),
            'USERPROFILE' => $home,
            'WINDIR' => getenv('WINDIR') ?: env('WINDIR'),
        ], static fn ($value) => $value !== null && $value !== '');

        return array_replace($currentEnv, $_ENV, $_SERVER, $env);
    }

    private function resolveConfigPath(): string
    {
        if ($this->configPath !== '') {
            return $this->configPath;
        }

        $stateDir = $this->resolveStateDir();
        return $stateDir !== '' ? $stateDir . DIRECTORY_SEPARATOR . 'openclaw.json' : '';
    }

    private function resolveStateDir(): string
    {
        if ($this->stateDir !== '') {
            return $this->stateDir;
        }

        if ($this->configPath !== '') {
            return dirname($this->configPath);
        }

        $home = $this->resolveHomeDirectory();
        return $home !== '' ? $home . DIRECTORY_SEPARATOR . '.openclaw' : '';
    }

    private function resolveHomeDirectory(): string
    {
        if ($this->stateDir !== '') {
            return dirname($this->stateDir);
        }

        if ($this->configPath !== '') {
            return dirname(dirname($this->configPath));
        }

        return (string) (env('USERPROFILE') ?: env('HOME') ?: '');
    }

    private function normalizeTextForWhatsapp(string $text): string
    {
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }

    private function resolveMediaReference(?string $mediaUrl): array
    {
        $mediaUrl = trim((string) $mediaUrl);
        if ($mediaUrl === '') {
            return ['url' => null, 'file' => null];
        }

        if (str_starts_with($mediaUrl, 'data:')) {
            Log::warning('Media WhatsApp berbentuk data URI tidak didukung gateway OpenClaw. Gunakan URL publik.', [
                'suggested_env' => 'OPENCLAW_PUBLIC_BASE_URL',
            ]);

            return ['url' => null, 'file' => null];
        }

        $parts = parse_url($mediaUrl);
        if ($parts === false) {
            return ['url' => null, 'file' => null];
        }

        if (!isset($parts['scheme']) || !isset($parts['host'])) {
            $rebuilt = $this->buildUrlFromBase($this->publicBaseUrl, $mediaUrl);
            if ($rebuilt !== null) {
                return ['url' => $rebuilt, 'file' => null];
            }

            return ['url' => null, 'file' => null];
        }

        if (!$this->isBlockedMediaHost($parts['host'])) {
            return ['url' => $mediaUrl, 'file' => null];
        }

        $rebuilt = $this->buildUrlFromBase($this->publicBaseUrl, $this->buildRelativePath($parts));
        if ($rebuilt !== null) {
            return ['url' => $rebuilt, 'file' => null];
        }

        Log::warning('Media WhatsApp masih memakai host lokal/private dan belum ada base URL publik.', [
            'original_media_url' => $mediaUrl,
            'suggested_env' => 'OPENCLAW_PUBLIC_BASE_URL',
        ]);

        return ['url' => null, 'file' => null];
    }

    private function buildRelativePath(array $parts): string
    {
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $path . $query;
    }

    private function resolveLocalMediaPath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $cleanPath = '/' . ltrim($path, '/');

        if (str_starts_with($cleanPath, '/storage/')) {
            $relative = ltrim(substr($cleanPath, strlen('/storage/')), '/');
            return storage_path('app/public/' . $relative);
        }

        return public_path(ltrim($cleanPath, '/'));
    }

    private function buildUrlFromBase(string $baseUrl, string $path): ?string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return null;
        }

        $baseParts = parse_url($baseUrl);
        if ($baseParts === false || !isset($baseParts['scheme'], $baseParts['host'])) {
            return null;
        }

        if ($this->isBlockedMediaHost($baseParts['host'])) {
            return null;
        }

        $normalizedPath = '/' . ltrim($path, '/');
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        return "{$baseParts['scheme']}://{$baseParts['host']}{$port}{$normalizedPath}";
    }

    private function isBlockedMediaHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return !filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function readGatewayTokenFromConfig(): string
    {
        $configPath = $this->resolveConfigPath();
        if ($configPath === '' || !is_file($configPath)) {
            return '';
        }

        try {
            $decoded = json_decode((string) file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca token gateway OpenClaw dari config.', [
                'config_path' => $configPath,
                'error' => $e->getMessage(),
            ]);

            return '';
        }

        $token = data_get($decoded, 'gateway.auth.token');

        return is_string($token) ? trim($token) : '';
    }

    private function resolveGatewayOrigin(string $gatewayUrl): string
    {
        if ($gatewayUrl === '') {
            return 'http://127.0.0.1:18789';
        }

        $parts = parse_url($gatewayUrl);
        if ($parts === false || !isset($parts['host'])) {
            return 'http://127.0.0.1:18789';
        }

        $scheme = ($parts['scheme'] ?? 'ws') === 'wss' ? 'https' : 'http';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}
