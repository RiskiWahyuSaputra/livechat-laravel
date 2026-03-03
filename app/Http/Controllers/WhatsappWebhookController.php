<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

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
                    // 1. Dapatkan respons dari Gemini AI via Service
                    $aiResponse = $this->geminiService->askGemini($userText);

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