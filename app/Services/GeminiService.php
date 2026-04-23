<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\QuickReply;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected string $provider;
    protected string $preferredModel;

    public function __construct(protected OpenClawService $openClawService)
    {
        $this->apiKey = trim(Setting::get('gemini_api_key', env('GEMINI_API_KEY') ?? ''));
        $this->provider = strtolower(trim((string) Setting::get('ai_provider', env('AI_PROVIDER', 'openclaw'))));
        $this->preferredModel = trim((string) Setting::get('gemini_model', env('GEMINI_MODEL', 'gemini-1.5-flash')));
    }

    public function askGemini($prompt, $additionalInstruction = "")
    {
        $fullInstruction = $this->buildAssistantInstruction($additionalInstruction);

        if ($this->shouldUseOpenClaw()) {
            $openClawResponse = $this->openClawService->ask($prompt, $fullInstruction);
            if ($openClawResponse) {
                return $openClawResponse;
            }
            Log::warning('OpenClaw tidak mengembalikan jawaban. Fallback ke Gemini API.');
        }

        $geminiResponse = $this->askGeminiApi($prompt, $fullInstruction);

        if ($geminiResponse) {
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
            $response = $this->openClawService->ask($prompt, "Kamu adalah AI Knowledge Extractor.");
            $data = $this->decodeKnowledgePayload($response);
            if (is_array($data)) {
                return $data;
            }

            Log::warning('OpenClaw tidak mengembalikan ringkasan knowledge. Fallback ke Gemini API.');
        }

        $response = $this->askGeminiApi($prompt, "Kamu adalah AI Knowledge Extractor.", [
            $this->preferredModel,
            'gemini-2.0-flash',
            'gemini-1.5-flash',
        ]);

        if ($response) {
            $data = $this->decodeKnowledgePayload($response);
            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    private function askGeminiApi(string $prompt, string $fullInstruction, ?array $preferredModels = null): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $models = array_values(array_unique(array_filter($preferredModels ?? [
            $this->preferredModel,
            'gemini-2.0-flash',
            'gemini-2.0-flash-001',
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash-lite-001',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
            'gemini-pro',
        ])));

        foreach ($models as $model) {
            $aiText = $this->tryModel($model, $fullInstruction, $prompt);
            if ($aiText) {
                return $aiText;
            }
        }

        // Jika semua model hardcoded gagal, coba ambil satu model dari API secara dinamis
        $availableModels = $this->getAvailableModels();
        if (!empty($availableModels)) {
            Log::info("Mencoba model alternatif dari API...");
            foreach ($availableModels as $modelData) {
                $modelName = $modelData['name']; // ini biasanya "models/..."
                // Hapus prefix "models/" jika ada karena URL sudah punya "models/"
                $modelId = str_replace('models/', '', $modelName);

                // Lewati jika sudah dicoba di loop sebelumnya
                if (in_array($modelId, $models, true)) {
                    continue;
                }

                $aiText = $this->tryModel($modelId, $fullInstruction, $prompt);
                if ($aiText) {
                    return $aiText;
                }
            }
        }

        return null;
    }

    private function tryModel($model, $fullInstruction, $prompt)
    {
        // Mencoba dengan Grounding (Google Search) terlebih dahulu
        $result = $this->executeRequest($model, $fullInstruction, $prompt, true);
        
        // Jika gagal atau tidak ada teks, coba lagi TANPA Grounding
        if (!$result) {
            Log::info("Gemini model {$model} gagal dengan Grounding, mencoba tanpa Grounding...");
            $result = $this->executeRequest($model, $fullInstruction, $prompt, false);
        }

        return $result;
    }

    private function executeRequest($model, $fullInstruction, $prompt, $useGrounding = true)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;

        try {
            $payload = [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]]
                ],
                'system_instruction' => [
                    'parts' => [['text' => $fullInstruction]]
                ],
            ];

            if ($useGrounding) {
                $payload['tools'] = [['google_search_retrieval' => new \stdClass()]];
            }

            $response = Http::withoutVerifying()
                ->timeout($useGrounding ? 15 : 8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $candidates = $data['candidates'] ?? [];
                
                if (!empty($candidates) && isset($candidates[0]['content']['parts'])) {
                    $fullText = "";
                    foreach ($candidates[0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $fullText .= $part['text'];
                        }
                    }

                    if (!empty(trim($fullText))) {
                        Log::info("Gemini Berhasil menggunakan model: {$model} " . ($useGrounding ? "(With Search)" : "(No Search)"));
                        return trim($fullText);
                    }
                }
                
                $finishReason = $candidates[0]['finishReason'] ?? 'UNKNOWN';
                Log::warning("Gemini model {$model} selesai tanpa teks. Reason: {$finishReason}", ['body' => $data]);
            } else {
                Log::warning("Gemini model {$model} API Error: " . $response->status(), ['body' => $response->json()]);
            }

        } catch (\Exception $e) {
            Log::error("Gemini Exception ({$model}): " . $e->getMessage());
        }

        return null;
    }

    private function getAvailableModels()
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $this->apiKey;
            $res = Http::withoutVerifying()->get($url);
            if ($res->successful()) {
                $data = $res->json();
                return $data['models'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error("Gagal mengambil daftar model: " . $e->getMessage());
        }
        return [];
    }

    private function logAvailableModels()
    {
        $models = $this->getAvailableModels();
        Log::info("Daftar Model Tersedia:", ['count' => count($models), 'models' => $models]);
    }

    private function fallbackMessage(): string
    {
        return "Maaf, sistem BEST AI lagi mengalami kendala nih. Coba lagi beberapa saat ya, atau ketik AGENT untuk terhubung langsung dengan Customer Service kami.";
    }

    private function shouldUseOpenClaw(): bool
    {
        return $this->provider === 'openclaw';
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
        13. Jika pengguna menanyakan kategori produk BEST seperti kecantikan, kesehatan, otomotif, pertanian, atau produk BEST secara umum, sistem dapat menampilkan gambar pendukung produk secara otomatis.
        14. Karena sistem bisa menampilkan gambar pendukung, jangan pernah bilang kamu tidak bisa mengirim foto, tidak bisa menampilkan gambar, atau tidak punya gambar produk jika memang pertanyaannya masih seputar kategori produk BEST.";

        $knowledgeBase = "\n\nKNOWLEDGE BASE (Gunakan informasi ini untuk menjawab):\n";
        foreach (QuickReply::all() as $quickReply) {
            $knowledgeBase .= "- {$quickReply->title}: {$quickReply->content}\n";
        }

        return trim($baseInstruction . $knowledgeBase . ' ' . $additionalInstruction);
    }

    private function decodeKnowledgePayload(?string $response): ?array
    {
        if (!$response) {
            return null;
        }

        $cleaned = preg_replace('/```json|```/', '', $response);
        $data = json_decode(trim($cleaned), true);

        return is_array($data) ? $data : null;
    }
}
