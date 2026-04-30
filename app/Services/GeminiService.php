<?php

namespace App\Services;

use App\Models\QuickReply;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $provider;
    protected string $preferredModel;
    protected string $groqApiKey;
    protected string $groqModel;
    protected int $responseCacheSeconds = 600;
    protected int $openClawBackoffSeconds = 300;

    public function __construct(protected OpenClawService $openClawService)
    {
        $this->apiKey = trim((string) Setting::get('gemini_api_key', env('GEMINI_API_KEY', '')));
        $this->provider = strtolower(trim((string) Setting::get('ai_provider', env('AI_PROVIDER', 'gemini'))));
        $this->preferredModel = trim((string) Setting::get('gemini_model', env('GEMINI_MODEL', 'gemini-2.0-flash-lite')));
        $this->groqApiKey = trim((string) Setting::get('groq_api_key', env('GROQ_API_KEY', '')));
        $this->groqModel = trim((string) Setting::get('groq_model', env('GROQ_MODEL', 'llama-3.3-70b-versatile')));
    }

    public function askGemini(string $prompt, string $additionalInstruction = ''): string
    {
        $fullInstruction = $this->buildAssistantInstruction($additionalInstruction);
        $cacheKey = $this->responseCacheKey($prompt, $fullInstruction);
        $cachedResponse = Cache::get($cacheKey);

        if (is_string($cachedResponse) && trim($cachedResponse) !== '') {
            return $cachedResponse;
        }

        if ($this->shouldUseOpenClaw()) {
            $openClawResponse = $this->openClawService->ask($prompt, $fullInstruction);

            if ($this->isUsableResponse($openClawResponse)) {
                Cache::put($cacheKey, $openClawResponse, $this->responseCacheSeconds);

                return $openClawResponse;
            }

            Cache::put($this->openClawBackoffCacheKey(), true, $this->openClawBackoffSeconds);
            Log::warning('OpenClaw tidak mengembalikan jawaban. Fallback ke Groq/Gemini API.', [
                'backoff_seconds' => $this->openClawBackoffSeconds,
            ]);
        }

        if ($this->shouldUseGroq()) {
            $groqResponse = $this->askGroqApi($prompt, $fullInstruction);

            if ($this->isUsableResponse($groqResponse)) {
                Cache::put($cacheKey, $groqResponse, $this->responseCacheSeconds);

                return $groqResponse;
            }

            Log::warning('Groq tidak mengembalikan jawaban. Fallback ke Gemini API.');
        }

        $geminiResponse = $this->askGeminiApi($prompt, $fullInstruction);

        if ($this->isUsableResponse($geminiResponse)) {
            Cache::put($cacheKey, $geminiResponse, $this->responseCacheSeconds);

            return $geminiResponse;
        }

        return $this->fallbackMessage();
    }

    public function summarizeConversation($history)
    {
        $prompt = "Berikut adalah riwayat percakapan antara Pelanggan dan Admin Support PT BEST CORP. 
        TUGAS ANDA: 
        1. Analisis apakah ada informasi Penting/Pertanyaan Baru yang berhasil dijawab oleh Admin dengan BAIK.
        2. KHUSUS: Jika Admin mengoreksi jawaban AI yang salah atau kurang lengkap sebelumnya, tandai ini sebagai KOREKSI.
        3. Buatlah ringkasan pengetahuan dalam format JSON array.
        4. Setiap elemen array harus punya:
           - 'title': (singkat, max 5 kata).
           - 'content': (jawaban lengkap dan profesional).
           - 'is_correction': (boolean, true jika ini memperbaiki jawaban AI sebelumnya).
           - 'old_title': (opsional, judul lama yang harus diganti jika is_correction true).
        5. HANYA ambil informasi yang BERGUNA. Abaikan basa-basi.
        6. Jika tidak ada informasi berguna, kembalikan [].
        7. Jawab HANYA dalam format JSON array asli, tanpa markdown block.

        RIWAYAT CHAT:
        $history";

        if ($this->shouldUseOpenClaw()) {
            $response = $this->openClawService->ask($prompt, 'Kamu adalah AI Knowledge Extractor.');
            $data = $this->decodeKnowledgePayload($response);

            if (is_array($data)) {
                return $data;
            }

            Cache::put($this->openClawBackoffCacheKey(), true, $this->openClawBackoffSeconds);
            Log::warning('OpenClaw tidak mengembalikan ringkasan knowledge. Fallback ke Groq/Gemini API.');
        }

        if ($this->shouldUseGroq()) {
            $response = $this->askGroqApi($prompt, 'Kamu adalah AI Knowledge Extractor.');
            $data = $this->decodeKnowledgePayload($response);

            if (is_array($data)) {
                return $data;
            }

            Log::warning('Groq tidak mengembalikan ringkasan knowledge. Fallback ke Gemini API.');
        }

        $response = $this->askGeminiApi($prompt, 'Kamu adalah AI Knowledge Extractor.', [
            $this->preferredModel,
            'gemma-4-26b-a4b-it',
            'gemini-2.0-flash-lite',
        ]);

        if ($response) {
            $data = $this->decodeKnowledgePayload($response);

            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    public function isFallbackResponse(?string $response): bool
    {
        $normalized = strtolower(trim(strip_tags((string) $response)));

        if ($normalized === '') {
            return true;
        }

        $fallbackPhrases = [
            'maaf, sistem best ai lagi mengalami kendala',
            'coba lagi beberapa saat ya',
            'terhubung langsung dengan customer service kami',
        ];

        foreach ($fallbackPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function askGeminiApi(string $prompt, string $fullInstruction, ?array $preferredModels = null): ?string
    {
        if ($this->apiKey === '') {
            return null;
        }

        foreach ($this->modelCandidates($preferredModels) as $model) {
            $aiText = $this->executeRequest($model, $fullInstruction, $prompt);

            if ($this->isUsableResponse($aiText)) {
                return $aiText;
            }
        }

        return null;
    }

    private function executeRequest(string $model, string $fullInstruction, string $prompt): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withoutVerifying()
                ->timeout(8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $fullInstruction],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                $this->markModelFailure($model, $response->status());
                Log::warning("Gemini model {$model} API Error: {$response->status()}", [
                    'body' => $response->json(),
                ]);

                return null;
            }

            $data = $response->json();
            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $fullText = '';

            foreach ($parts as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    $fullText .= $part['text'];
                }
            }

            $fullText = trim($fullText);

            if ($fullText !== '') {
                Cache::forget($this->modelFailureCacheKey($model));
                Log::info("Gemini berhasil menggunakan model: {$model}");

                return $fullText;
            }

            Log::warning("Gemini model {$model} selesai tanpa teks.", [
                'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'UNKNOWN',
            ]);
        } catch (\Throwable $e) {
            Log::error("Gemini Exception ({$model}): " . $e->getMessage());
        }

        return null;
    }

    private function modelCandidates(?array $preferredModels = null): array
    {
        $defaults = [
            $this->preferredModel,
            'gemma-4-26b-a4b-it',
            'gemini-2.0-flash-lite',
        ];

        return array_values(array_filter(
            array_unique(array_filter($preferredModels ?? $defaults, static fn ($model) => is_string($model) && trim($model) !== '')),
            fn (string $model) => !$this->isModelInBackoffWindow($model)
        ));
    }

    private function fallbackMessage(): string
    {
        return 'Maaf, sistem BEST AI lagi mengalami kendala nih. Coba lagi beberapa saat ya, atau ketik AGENT untuk terhubung langsung dengan Customer Service kami.';
    }

    private function shouldUseOpenClaw(): bool
    {
        return $this->provider === 'openclaw'
            && !$this->isOpenClawInBackoffWindow();
    }

    private function shouldUseGroq(): bool
    {
        return $this->provider === 'groq' && $this->groqApiKey !== '';
    }

    private function askGroqApi(string $prompt, string $fullInstruction): ?string
    {
        if ($this->groqApiKey === '') {
            return null;
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($this->groqApiKey)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->groqModel,
                    'messages' => [
                        ['role' => 'system', 'content' => $fullInstruction],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 1024,
                ]);

            if (!$response->successful()) {
                Log::warning("Groq API Error: {$response->status()}", [
                    'body' => $response->json(),
                ]);

                return null;
            }

            $text = $response->json('choices.0.message.content');

            if (is_string($text) && trim($text) !== '') {
                Log::info("Groq berhasil menjawab dengan model: {$this->groqModel}");

                return trim($text);
            }

            Log::warning("Groq mengembalikan respons kosong.");
        } catch (\Throwable $e) {
            Log::error("Groq Exception: " . $e->getMessage());
        }

        return null;
    }

    private function isOpenClawInBackoffWindow(): bool
    {
        return (bool) Cache::get($this->openClawBackoffCacheKey(), false);
    }

    private function openClawBackoffCacheKey(): string
    {
        return 'best_ai_openclaw_backoff';
    }

    private function responseCacheKey(string $prompt, string $fullInstruction): string
    {
        return 'best_ai_response_' . sha1(
            $this->provider . '|' . $this->preferredModel . '|' . $this->normalizePromptForCache($prompt) . '|' . $fullInstruction
        );
    }

    private function isUsableResponse(?string $response): bool
    {
        return is_string($response) && trim($response) !== '' && !$this->isFallbackResponse($response);
    }

    private function normalizePromptForCache(string $prompt): string
    {
        $normalized = preg_replace('/\s+\[(web|wa) user \d+ #\d+\]$/i', '', trim($prompt));

        return is_string($normalized) && $normalized !== '' ? $normalized : trim($prompt);
    }

    private function isModelInBackoffWindow(string $model): bool
    {
        return (bool) Cache::get($this->modelFailureCacheKey($model), false);
    }

    private function modelFailureCacheKey(string $model): string
    {
        return 'best_ai_model_backoff_' . sha1($model);
    }

    private function markModelFailure(string $model, int $statusCode): void
    {
        $seconds = match ($statusCode) {
            404, 400 => 3600,
            429 => 120,
            default => 300,
        };

        Cache::put($this->modelFailureCacheKey($model), true, $seconds);
    }

    private function buildAssistantInstruction(string $additionalInstruction = ''): string
    {
        $baseInstruction = "Kamu adalah BEST AI, asisten virtual resmi milik PT BEST CORPORATION SYARIAH.
        IDENTITAS DAN BATASAN:
        1. Kamu hanya boleh menjawab hal-hal yang berkaitan dengan PT BEST CORPORATION SYARIAH, termasuk profil perusahaan, produk, layanan, pendaftaran, benefit, promo, prosedur, dan informasi resmi lain yang berhubungan langsung dengan PT BEST CORPORATION SYARIAH.
        2. Jika pengguna bertanya di luar konteks PT BEST CORPORATION SYARIAH, kamu wajib menolak dengan sopan. Jangan menjawab isi pertanyaan tersebut.
        3. Saat menolak, arahkan pengguna untuk kembali bertanya seputar PT BEST CORPORATION SYARIAH. Contoh gaya jawaban: 'Maaf, saya hanya bisa membantu pertanyaan seputar PT BEST CORPORATION SYARIAH. Kalau kamu mau, silakan tanyakan produk, layanan, atau informasi BEST ya.'
        4. Jangan pernah mengarang jawaban. Jika informasi tidak ada di knowledge base atau data yang tersedia, katakan dengan jujur bahwa kamu belum memiliki detail datanya dan sarankan menunggu admin atau agent.
        GAYA JAWABAN:
        5. Jawab singkat, padat, jelas, dan ramah dalam bahasa Indonesia.
        6. Gunakan kata 'kamu', bukan 'Anda'.
        7. Jika membuat daftar, gunakan format angka: 1, 2, 3, dan seterusnya.
        8. Jangan gunakan tanda ** untuk bold.
        9. Jangan gunakan tanda kurung siku [] atau placeholder palsu.
        10. Jangan beri salam pembuka di awal jawaban. Langsung jawab inti.
        11. Jika pengguna meminta bantuan manusia, admin, atau agent, arahkan untuk klik tombol Hubungi Agent yang tersedia.
        12. Prioritaskan knowledge base di bawah sebagai sumber utama jawaban.
        13. Jika pengguna menanyakan produk, jelaskan kategori produk sesuai knowledge base dan sesuaikan dengan kategori yang ada di website support, seperti produk additif untuk kendaraan, produk pupuk untuk pertanian dan perkebunan, produk herbal untuk kesehatan, produk skincare dan kecantikan, produk minuman untuk kesehatan tubuh, serta produk pembersih untuk kesehatan area tubuh.
        14. Jika pengguna menanyakan kategori atau jenis produk, prioritaskan menjawab berdasarkan kategori resmi tersebut sebelum masuk ke nama produk spesifik.
        15. Jika pengguna menanyakan kategori produk BEST seperti kecantikan, kesehatan, otomotif, pertanian, perkebunan, minuman kesehatan, pembersih area tubuh, atau produk BEST secara umum, sistem dapat menampilkan gambar pendukung produk secara otomatis.
        16. Nama produk spesifik yang ada di knowledge base atau katalog gambar internal seperti Agrosawit, Eco Racing, Eco Diesel, B-MAXX, HABSPRO, ECO VICO, LVN Serum, dan produk BEST lainnya tetap dianggap sebagai konteks PT BEST meskipun user tidak menulis kata PT BEST secara eksplisit.
        17. Karena sistem bisa menampilkan gambar pendukung, jangan pernah bilang kamu tidak bisa mengirim foto, tidak bisa menampilkan gambar, atau tidak punya gambar produk jika memang pertanyaannya masih seputar kategori produk BEST.";

        $knowledgeRows = Cache::remember('best_ai_quick_reply_knowledge', 300, function () {
            return QuickReply::query()
                ->get(['title', 'content'])
                ->map(fn ($quickReply) => [
                    'title' => $quickReply->title,
                    'content' => $quickReply->content,
                ])
                ->all();
        });

        $knowledgeBase = "\n\nKNOWLEDGE BASE (Gunakan informasi ini untuk menjawab):\n";
        foreach ($knowledgeRows as $quickReply) {
            $knowledgeBase .= "- {$quickReply['title']}: {$quickReply['content']}\n";
        }

        return trim($baseInstruction . $knowledgeBase . ' ' . $additionalInstruction);
    }

    private function decodeKnowledgePayload(?string $response): ?array
    {
        if (!$response) {
            return null;
        }

        $cleaned = preg_replace('/```json|```/', '', $response);
        $data = json_decode(trim((string) $cleaned), true);

        return is_array($data) ? $data : null;
    }
}
