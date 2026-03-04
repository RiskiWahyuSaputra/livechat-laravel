<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $token;
    protected $adminNumber;

    public function __construct()
    {
        $this->token = env('WHAPI_TOKEN');
        $this->adminNumber = env('WHAPI_ADMIN_NUMBER');
    }

    /**
     * Kirim pesan teks ke nomor tertentu
     */
    public function sendMessage($to, $text, $typingTime = 2)
    {
        if (!$this->token) {
            Log::warning("WHAPI_TOKEN tidak ditemukan di .env");
            return false;
        }

        // Pastikan format nomor benar (tambah @s.whatsapp.net jika belum ada)
        if (!str_contains($to, '@')) {
            $to = $to . '@s.whatsapp.net';
        }

        $response = Http::withToken($this->token)
            ->post("https://gate.whapi.cloud/messages/text", [
                'to' => $to,
                'body' => $text,
                'typing_time' => $typingTime
            ]);

        if ($response->failed()) {
            Log::error("WHAPI Send Error to $to: " . $response->body());
        }
        
        return $response->successful();
    }

    /**
     * Kirim pesan ke admin
     */
    public function notifyAdmin($text)
    {
        if (!$this->adminNumber) {
            Log::warning("WHAPI_ADMIN_NUMBER tidak ditemukan di .env");
            return false;
        }

        return $this->sendMessage($this->adminNumber, $text);
    }

    /**
     * Kirim file/media
     */
    public function sendMedia($to, $mediaUrl, $caption = '', $type = 'image')
    {
        if (!$this->token) return false;

        if (!str_contains($to, '@')) {
            $to = $to . '@s.whatsapp.net';
        }

        $endpoint = $type === 'image' ? 'image' : 'document';
        
        $response = Http::withToken($this->token)
            ->post("https://gate.whapi.cloud/messages/$endpoint", [
                'to' => $to,
                'media' => $mediaUrl,
                'caption' => $caption
            ]);

        return $response->successful();
    }
}
