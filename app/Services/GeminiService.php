<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = trim(Setting::get('gemini_api_key', env('GEMINI_API_KEY') ?? ''));
    }

    public function askGemini($prompt, $additionalInstruction = "")
    {
        if (empty($this->apiKey)) return "Sistem AI belum siap.";

        // Coba model-model ini secara berurutan
        $models = [
            'gemini-2.0-flash', 
            'gemini-1.5-flash', 
            'gemini-1.5-pro',
            'gemini-pro'
        ];
        
        $baseInstruction = "Anda adalah asisten AI resmi dari PT BEST CORPORATION SYARIAH. 
        TUGAS ANDA:
        1. Hanya jawab pertanyaan yang berkaitan dengan profil, produk, layanan, pendaftaran, dan informasi seputar PT BEST CORPORATION SYARIAH.
        2. Jika pertanyaan di luar topik tersebut (seperti politik, agama umum, tips masak, teknologi lain, dll), tolak dengan sopan dan arahkan pelanggan untuk bertanya seputar PT BEST CORP.
        3. Jika memberikan jawaban dalam bentuk daftar atau list, wajib gunakan format angka (1, 2, 3, dst).
        4. Jawab dengan singkat, padat, dan sangat profesional dalam bahasa Indonesia.
        5. jangan gunakan ** untuk membuat teks menjadi bold.
        6. JANGAN PERNAH menggunakan tanda kurung [] atau placeholder seperti '[Sebutkan produk...]'.
        7. Jika informasi tidak ditemukan di KNOWLEDGE BASE di bawah, beritahu pelanggan bahwa Anda belum memiliki data detailnya dan minta mereka menunggu admin, JANGAN MENEBAK.";
        
        // Tambahkan Knowledge Base dari QuickReply
        $quickReplies = \App\Models\QuickReply::all();
        $knowledgeBase = "\n\nKNOWLEDGE BASE (Gunakan informasi ini untuk menjawab):\n";
        foreach ($quickReplies as $qr) {
            $knowledgeBase .= "- {$qr->title}: {$qr->content}\n";
        }

        $fullInstruction = $baseInstruction . $knowledgeBase . " " . $additionalInstruction;

        foreach ($models as $model) {
            $aiText = $this->tryModel($model, $fullInstruction, $prompt);
            if ($aiText) return $aiText;
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
                if (in_array($modelId, $models)) continue;

                $aiText = $this->tryModel($modelId, $fullInstruction, $prompt);
                if ($aiText) return $aiText;
            }
        }

        return "Maaf, admin sedang tidak di tempat. Pesan Anda sudah kami catat.";
    }

    public function summarizeConversation($history)
    {
        if (empty($this->apiKey)) return null;

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

        // Gunakan model yang mumpuni untuk ekstraksi
        $models = ['gemini-2.0-flash', 'gemini-1.5-flash'];
        
        foreach ($models as $model) {
            $response = $this->tryModel($model, "Anda adalah AI Knowledge Extractor.", $prompt);
            if ($response) {
                // Bersihkan respon dari markdown jika AI membandel
                $cleaned = preg_replace('/```json|```/', '', $response);
                $data = json_decode(trim($cleaned), true);
                if (is_array($data)) return $data;
            }
        }

        return null;
    }

    private function tryModel($model, $fullInstruction, $prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;

        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [['parts' => [['text' => "Instruksi: $fullInstruction\n\nUser: $prompt"]]]],
                    'tools' => [['google_search_retrieval' => new \stdClass()]]
                ]);

            if ($response->successful()) {
                $candidates = $response->json('candidates');
                if (!empty($candidates) && isset($candidates[0]['content']['parts'])) {
                    $fullText = "";
                    foreach ($candidates[0]['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            // Abaikan bagian thinking jika ada (biasanya di model 2.0 thinking)
                            // Meskipun biasanya thinking ada di field terpisah atau punya metadata,
                            // kita ambil semua teks yang tersedia.
                            $fullText .= $part['text'];
                        }
                    }

                    if (!empty(trim($fullText))) {
                        Log::info("Gemini Berhasil menggunakan model: {$model}");
                        return trim($fullText);
                    }
                }
                Log::warning("Gemini model {$model} sukses tapi tidak ada teks:", ['body' => $response->json()]);
            }

            Log::warning("Gemini model {$model} gagal: " . $response->status(), ['body' => $response->json()]);

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
}
