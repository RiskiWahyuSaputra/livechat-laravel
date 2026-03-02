<?php

namespace App\Http\Controllers;

use App\Models\Chat; // Pastikan model Chat sudah dibuat
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Menangani webhook masuk dari WHAPI
     */
    public function handle(Request $request)
    {
        Log::info('Payload dari WHAPI:', $request->all());

        $messages = $request->input('messages');

        if (!$messages) {
            return response()->json(['status' => 'no messages']);
        }

        foreach ($messages as $msg) {
            // Hindari membalas pesan dari diri sendiri
            if (isset($msg['from_me']) && $msg['from_me']) {
                continue;
            }

            $chatId = $msg['chat_id'];
            $userText = $msg['text']['body'] ?? '';
            $userName = $msg['from_name'] ?? 'Pelanggan';

            if (!empty($userText)) {
                try {
                    // 1. Dapatkan respons dari Gemini AI
                    $aiResponse = $this->askGemini($userText);

                    // 2. Simpan ke database untuk menu Riwayat Arsip
                    Chat::create([
                        'whatsapp_id' => $chatId,
                        'name' => $userName,
                        'message' => $userText,
                        'response' => $aiResponse,
                    ]);

                    // 3. Kirim balik ke WhatsApp via WHAPI
                    $this->sendWhapiMessage($chatId, $aiResponse);

                } catch (\Exception $e) {
                    Log::error("Gagal memproses pesan untuk $chatId: " . $e->getMessage());
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Memanggil API Gemini dengan penanganan error 404
     */
    private function askGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');
        
        // Menggunakan alias gemini-flash-latest (seringkali memiliki kuota lebih stabil di free tier)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                ['parts' => [['text' => "Anda adalah admin helpdesk Best Corporation. Jawablah secara singkat: " . $prompt]]]
            ]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Gagal (" . $response->status() . "): " . $response->body());
            
            // Jika flash-latest masih gagal, coba pro-latest sebagai upaya terakhir
            if ($response->status() == 429) {
                $urlPro = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-latest:generateContent?key=" . $apiKey;
                $responsePro = Http::post($urlPro, [
                    'contents' => [['parts' => [['text' => "Jawablah singkat: " . $prompt]]]]
                ]);
                
                if ($responsePro->successful()) {
                    return $responsePro->json('candidates.0.content.parts.0.text');
                }
            }

            return "Maaf, admin sedang tidak di tempat. Pesan Anda sudah kami catat di Riwayat Arsip.";
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * Mengirim pesan via WHAPI
     */
    private function sendWhapiMessage($to, $text)
    {
        $response = Http::withToken(env('WHAPI_TOKEN'))
            ->post("https://gate.whapi.cloud/messages/text", [
                'to' => $to,
                'body' => $text,
                'typing_time' => 2
            ]);

        if ($response->failed()) {
            Log::error("WHAPI Send Error: " . $response->body());
        }
        
        return $response;
    }
}