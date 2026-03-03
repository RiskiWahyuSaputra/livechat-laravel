<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $primaryModel = 'gemini-2.0-flash';
    protected $backupModel = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Memanggil API Gemini untuk mendapatkan respons AI
     */
    public function askGemini($prompt, $systemInstruction = "Anda adalah admin helpdesk Best Corporation. Jawablah secara singkat.")
    {
        if (!$this->apiKey) {
            Log::warning("GEMINI_API_KEY tidak ditemukan di .env");
            return "Maaf, sistem AI sedang tidak tersedia.";
        }

        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->primaryModel}:generateContent?key=" . $this->apiKey;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    ['parts' => [['text' => "$systemInstruction $prompt"]]]
                ]
            ]);

            if ($response->successful()) {
                $aiText = $response->json('candidates.0.content.parts.0.text');
                return $aiText ?: "Maaf, saya tidak dapat memproses permintaan Anda saat ini.";
            }

            Log::error("Gemini API Gagal (" . $response->status() . "): " . $response->body());

            // Backup menggunakan model cadangan
            $urlBackup = "https://generativelanguage.googleapis.com/v1/models/{$this->backupModel}:generateContent?key=" . $this->apiKey;
            $responseBackup = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($urlBackup, [
                'contents' => [
                    ['parts' => [['text' => "$systemInstruction $prompt"]]]
                ]
            ]);

            if ($responseBackup->successful()) {
                $backupText = $responseBackup->json('candidates.0.content.parts.0.text');
                return $backupText ?: "Maaf, admin sedang tidak di tempat.";
            }

        } catch (\Exception $e) {
            Log::error("Exception Gemini Service: " . $e->getMessage());
        }

        return "Maaf, admin sedang tidak di tempat. Pesan Anda sudah kami catat.";
    }
}
