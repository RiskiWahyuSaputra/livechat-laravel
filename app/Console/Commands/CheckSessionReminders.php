<?php

namespace App\Console\Commands;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckSessionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-session-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks idle conversations and sends reminders about session timeout every 5 minutes';

    /**
     * Session lifetime in minutes
     */
    protected $sessionLifetime = 30;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get active or pending conversations that have some last_message_at
        $conversations = Conversation::whereIn('status', ['active', 'pending', 'queued'])
            ->whereNotNull('last_message_at')
            ->get();

        $now = now();

        foreach ($conversations as $conversation) {
            $lastActivity = $conversation->last_message_at;
            $idleMinutes = (int) $lastActivity->diffInMinutes($now);

            // Calculate remaining time
            $remainingMinutes = $this->sessionLifetime - $idleMinutes;

            // Handle session expiration
            if ($remainingMinutes <= 0) {
                $this->expireSession($conversation);
                continue;
            }

            // Reminders every 5 minutes (5, 10, 15, 20, 25 mins idle)
            if ($idleMinutes > 0 && $idleMinutes % 5 == 0 && $idleMinutes < $this->sessionLifetime) {
                // Check cache to prevent double sending for the same minute
                $cacheKey = "conversation_reminder_{$conversation->id}_{$idleMinutes}";
                
                if (!Cache::has($cacheKey)) {
                    $this->sendReminder($conversation, $remainingMinutes);
                    Cache::put($cacheKey, true, 60); // Store for 1 minute
                }
            }
        }

        return 0;
    }

    /**
     * Send reminder system message
     */
    protected function sendReminder(Conversation $conversation, int $remaining)
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0, // system
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => "Sesi percakapan Anda akan berakhir dalam {$remaining} menit jika tidak ada aktivitas.",
            'is_read'         => false,
        ]);

        try {
            broadcast(new MessageSent($message));
        } catch (\Exception $e) {
            \Log::error("Failed to broadcast reminder: " . $e->getMessage());
        }
    }

    /**
     * Handle session expiration (close chat)
     */
    protected function expireSession(Conversation $conversation)
    {
        $cacheKey = "conversation_expired_{$conversation->id}";
        if (Cache::has($cacheKey)) return;

        $conversation->update([
            'status' => 'closed',
            'deleted_at' => null,
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0, // system
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Sesi percakapan Anda telah berakhir karena tidak ada aktivitas selama 30 menit.',
            'is_read'         => false,
        ]);

        try {
            broadcast(new MessageSent($message));
            if ($conversation->admin) {
                broadcast(new ConversationStatusChanged($conversation, 'System'));
            }
            Cache::put($cacheKey, true, 60);
        } catch (\Exception $e) {
            \Log::error("Failed to expire conversation: " . $e->getMessage());
        }
    }
}
