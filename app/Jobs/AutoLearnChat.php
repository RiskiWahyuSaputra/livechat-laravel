<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\QuickReply;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoLearnChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversationId;

    public function __construct($conversationId)
    {
        $this->conversationId = $conversationId;
    }

    public function handle(GeminiService $geminiService)
    {
        $conversation = Conversation::withTrashed()->find($this->conversationId);
        if (!$conversation) return;

        // Ambil pesan-pesan yang relevan (hanya user dan admin)
        $messages = $conversation->messages()
            ->whereIn('sender_type', ['user', 'admin'])
            ->where('message_type', 'text')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($messages->count() < 4) return; // Terlalu pendek untuk dipelajari

        $historyText = "";
        foreach ($messages as $msg) {
            $sender = ($msg->sender_type === 'admin' && $msg->sender_id == 0) ? 'AI' : strtoupper($msg->sender_type);
            $historyText .= "[{$sender}]: {$msg->content}\n";
        }

        $newKnowledge = $geminiService->summarizeConversation($historyText);

        if (!empty($newKnowledge) && is_array($newKnowledge)) {
            foreach ($newKnowledge as $item) {
                if (!empty($item['title']) && !empty($item['content'])) {
                    
                    // Jika ini adalah koreksi, hapus data lama yang dikoreksi
                    if (!empty($item['is_correction']) && $item['is_correction'] === true) {
                        $targetTitle = $item['old_title'] ?? $item['title'];
                        QuickReply::where('title', 'like', '%' . $targetTitle . '%')->delete();
                        Log::info("Bot menghapus pengetahuan lama karena ada koreksi Admin: " . $targetTitle);
                    }

                    // Cek apakah sudah ada yang persis sama
                    $exists = QuickReply::where('title', $item['title'])->exists();
                    if (!$exists) {
                        QuickReply::create([
                            'title' => $item['title'],
                            'content' => $item['content']
                        ]);
                        Log::info("Bot telah mempelajari pengetahuan baru/koreksi: " . $item['title']);
                    }
                }
            }
        }
    }
}
