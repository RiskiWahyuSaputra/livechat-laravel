<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\ConversationStatusChanged;
use App\Events\UserShouldBeLoggedOut;
use Carbon\Carbon;

class CheckInactivityReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:check-inactivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for inactive conversations and send reminders every 5 minutes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ambil percakapan yang aktif
        $conversations = Conversation::whereIn('status', ['pending', 'active', 'queued'])
            ->whereNotNull('last_message_at')
            ->get();

        foreach ($conversations as $conversation) {
            $lastMessageAt = Carbon::parse($conversation->last_message_at);
            $minutesInactive = now()->diffInMinutes($lastMessageAt);

            // Interval pengingat (setiap 5 menit)
            $interval = 5;
            $maxTime = config('session.lifetime', 30);
            
            // Hitung harusnya sudah berapa kali diingatkan
            $expectedReminderCount = floor($minutesInactive / $interval);

            // Jika sudah mencapai batas waktu maksimal (30 menit)
            if ($minutesInactive >= $maxTime) {
                $this->closeConversation($conversation);
                continue;
            }

            // Jika waktunya untuk mengirim pengingat baru
            if ($expectedReminderCount > $conversation->reminder_count && $expectedReminderCount > 0) {
                $remainingTime = $maxTime - ($expectedReminderCount * $interval);
                $this->sendReminder($conversation, $expectedReminderCount, $remainingTime);
            }
        }
    }

    /**
     * Kirim pesan pengingat ke user.
     */
    private function sendReminder(Conversation $conversation, $count, $remainingTime)
    {
        $messageContent = "Sesi chat Anda akan berakhir dalam {$remainingTime} menit jika tidak ada aktivitas.";
        
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0, // System
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => $messageContent,
        ]);

        $conversation->update([
            'reminder_count' => $count
        ]);

        try {
            broadcast(new MessageSent($message));
            $this->info("Reminder sent to Conversation #{$conversation->id} ({$count}x)");
        } catch (\Exception $e) {
            $this->error("Failed to broadcast reminder: " . $e->getMessage());
        }
    }

    /**
     * Tutup percakapan karena tidak ada aktivitas.
     */
    private function closeConversation(Conversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
            'reminder_count' => 6 // Tandai sudah melewati batas
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Sesi chat Anda telah berakhir secara otomatis karena tidak ada aktivitas selama 30 menit.',
        ]);

        try {
            broadcast(new MessageSent($message));
            broadcast(new ConversationStatusChanged($conversation, 'System'));
            
            if ($conversation->customer) {
                event(new UserShouldBeLoggedOut($conversation->customer));
            }
            
            $this->info("Conversation #{$conversation->id} closed due to inactivity.");
        } catch (\Exception $e) {
            $this->error("Failed to broadcast closure: " . $e->getMessage());
        }
    }
}
