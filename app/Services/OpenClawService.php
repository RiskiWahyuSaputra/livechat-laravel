<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenClawService
{
    protected string $baseUrl;
    protected string $hookPath;
    protected string $hookToken;
    protected string $agentName;
    protected string $model;
    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) Setting::get('openclaw_base_url', env('OPENCLAW_BASE_URL', 'http://127.0.0.1:18789')), '/');
        $this->hookPath = '/' . ltrim((string) Setting::get('openclaw_hook_path', env('OPENCLAW_HOOK_PATH', '/hooks/agent')), '/');
        $this->hookToken = trim((string) Setting::get('openclaw_hook_token', env('OPENCLAW_HOOK_TOKEN', '')));
        $this->agentName = trim((string) Setting::get('openclaw_agent_name', env('OPENCLAW_AGENT_NAME', 'Website AI')));
        $this->model = trim((string) Setting::get('openclaw_model', env('OPENCLAW_MODEL', 'codex')));
        $this->timeoutSeconds = max(5, (int) Setting::get('openclaw_timeout_seconds', env('OPENCLAW_TIMEOUT_SECONDS', 30)));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    public function ask(string $prompt, string $systemInstruction = ''): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('OpenClaw belum dikonfigurasi lengkap.');
            return null;
        }

        try {
            $payload = [
                'message' => $this->buildAgentPrompt($prompt, $systemInstruction),
                'name' => $this->agentName !== '' ? $this->agentName : 'Website AI',
                'deliver' => 'none',
                'timeoutSeconds' => $this->timeoutSeconds,
            ];

            if ($this->model !== '') {
                $payload['model'] = $this->model;
            }

            $request = Http::withoutVerifying()
                ->acceptJson()
                ->timeout($this->timeoutSeconds + 5);

            if ($this->hookToken !== '') {
                $request = $request->withToken($this->hookToken);
            }

            $response = $request->post($this->baseUrl . $this->hookPath, $payload);

            if (!$response->successful()) {
                Log::warning('OpenClaw hook request gagal.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $text = $this->extractResponseText($response->json());

            if (!$text) {
                $rawBody = trim($response->body());
                if ($rawBody !== '' && !str_starts_with($rawBody, '<')) {
                    return $rawBody;
                }

                Log::warning('OpenClaw berhasil dipanggil tetapi teks jawaban tidak ditemukan.', [
                    'body' => $response->body(),
                ]);
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('OpenClaw Exception: ' . $e->getMessage());
            return null;
        }
    }

    private function buildAgentPrompt(string $prompt, string $systemInstruction = ''): string
    {
        $sections = [];

        if (trim($systemInstruction) !== '') {
            $sections[] = "Ikuti instruksi sistem berikut dengan ketat.\n[SYSTEM]\n" . trim($systemInstruction) . "\n[/SYSTEM]";
        }

        $sections[] = "Pesan pelanggan:\n" . trim($prompt);
        $sections[] = "Balas hanya isi jawaban yang akan dikirim ke pelanggan. Jangan tambahkan penjelasan internal.";

        return implode("\n\n", $sections);
    }

    private function extractResponseText(mixed $payload): ?string
    {
        if (is_string($payload)) {
            $payload = trim($payload);
            return $payload !== '' ? $payload : null;
        }

        if (!is_array($payload)) {
            return null;
        }

        foreach (['finalOutput', 'final_output', 'output', 'text', 'response', 'reply', 'content', 'summary', 'message', 'result'] as $key) {
            if (array_key_exists($key, $payload)) {
                $value = $this->extractResponseText($payload[$key]);
                if ($value) {
                    return $value;
                }
            }
        }

        foreach ($payload as $key => $value) {
            if (in_array($key, ['status', 'ok', 'success', 'id', 'taskId', 'agentId', 'name', 'channel', 'to'], true)) {
                continue;
            }

            $text = $this->extractResponseText($value);
            if ($text) {
                return $text;
            }
        }

        return null;
    }
}
