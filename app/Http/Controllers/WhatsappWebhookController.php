<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\WhatsappSession;
use App\Services\FlowEngine;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    protected $geminiService;
    protected $flowEngine;

    public function __construct(GeminiService $geminiService, FlowEngine $flowEngine)
    {
        $this->geminiService = $geminiService;
        $this->flowEngine = $flowEngine;
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

            $chatId   = $msg['chat_id'];
            $userText = $msg['text']['body'] ?? '';
            $userName = $msg['from_name'] ?? 'Pelanggan';

            if (!empty($userText)) {
                try {
                    if (config('chat.use_flow_engine', true)) {
                        $this->handleWithFlowEngine($chatId, $userName, $userText);
                    } else {
                        $this->handleWithGemini($chatId, $userName, $userText);
                    }
                } catch (\Exception $e) {
                    Log::error("Gagal memproses pesan untuk $chatId: " . $e->getMessage());
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Process message through the flow engine.
     */
    private function handleWithFlowEngine(string $chatId, string $userName, string $userText): void
    {
        $session = WhatsappSession::findOrCreateForChat($chatId, $userName);
        $session->update([
            'user_name'        => $userName,
            'last_activity_at' => now(),
        ]);

        $replies = $this->flowEngine->handle($session, $userText);

        foreach ($replies as $reply) {
            // Archive to Chat history
            Chat::create([
                'whatsapp_id' => $chatId,
                'name'        => $userName,
                'message'     => $userText,
                'response'    => $reply,
            ]);

            $this->sendWhapiMessage($chatId, $reply);
        }
    }

    /**
     * Legacy: process message via Gemini AI directly.
     */
    private function handleWithGemini(string $chatId, string $userName, string $userText): void
    {
        $aiResponse = $this->geminiService->askGemini($userText);

        Chat::create([
            'whatsapp_id' => $chatId,
            'name'        => $userName,
            'message'     => $userText,
            'response'    => $aiResponse,
        ]);

        $this->sendWhapiMessage($chatId, $aiResponse);
    }

    /**
     * Mengirim pesan via WHAPI
     */
    private function sendWhapiMessage($to, $text)
    {
        $token = \App\Models\Setting::get('whapi_token', env('WHAPI_TOKEN'));
        
        $response = Http::withToken($token)
            ->post("https://gate.whapi.cloud/messages/text", [
                'to'          => $to,
                'body'        => $text,
                'typing_time' => 2,
            ]);

        if ($response->failed()) {
            Log::error("WHAPI Send Error: " . $response->body());
        }
        
        return $response;
    }
}
