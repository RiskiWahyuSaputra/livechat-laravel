<?php
/*
|--------------------------------------------------------------------------
| Job: ProcessUserMessage
|--------------------------------------------------------------------------
| Handles WhatsApp notifications and Gemini AI responses for user messages in the background.
| This prevents the guest chat widget and user dashboard from lagging.
*/

namespace App\Jobs;

use App\Models\Message;
use App\Models\Conversation;
use App\Services\WhatsappService;
use App\Services\GeminiService;
use App\Events\MessageSent;
use App\Events\ConversationStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUserMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(WhatsappService $whatsappService, GeminiService $geminiService)
    {
        try {
            $conversation = $this->message->conversation;
            if (!$conversation) return;

            $user = $conversation->user;
            if (!$user) return;

            $messageType = $this->message->message_type;
            $content = $this->message->content;

            // --- WHAPI NOTIFICATION ---
            if ($conversation->bot_phase === 'off' || !$conversation->bot_phase) {
                $adminText = "💬 Pesan baru!\nDari: {$user->name} ({$user->origin})\nIsi: " . ($messageType === 'text' ? $content : "[Media]");
                try {
                    $whatsappService->notifyAdmin($adminText);
                    if ($messageType !== 'text') {
                        $whatsappService->sendMedia(env('WHAPI_ADMIN_NUMBER'), $content, "Media dari {$user->name}", $messageType);
                    }
                } catch (\Exception $waEx) {
                    Log::error("WhatsApp Notification failed in Job: " . $waEx->getMessage());
                }
            }

            // --- AI AUTO RESPONSE ---
            if (!$conversation->admin_id && $messageType === 'text' && ($conversation->bot_phase === 'off' || !$conversation->bot_phase)) {
                $aiAutoResponse = $geminiService->askGemini($content, "Berikan jawaban singkat:");
                
                if ($aiAutoResponse) {
                    $aiMessage = Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id'       => 0,
                        'sender_type'     => 'admin',
                        'message_type'    => 'text',
                        'content'         => '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>' . $aiAutoResponse,
                    ]);

                    try {
                        broadcast(new MessageSent($aiMessage));
                    } catch (\Exception $bcEx) {
                        Log::error("AI Message Broadcast failed in Job: " . $bcEx->getMessage());
                    }
                }
            }

            // --- BOT RESPONSE HANDLER (Optional move) ---
            // If the message triggers a bot state change, we might want to handle it here too.
            // But usually this responds via JSON in the main request for better UX.

            Log::info("ProcessUserMessage Job Success for message ID: " . $this->message->id);
        } catch (\Exception $e) {
            Log::error("ProcessUserMessage Job Failed: " . $e->getMessage());
        }
    }
}
