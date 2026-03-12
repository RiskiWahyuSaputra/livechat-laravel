<?php
/*
|--------------------------------------------------------------------------
| Job: ProcessAdminMessage
|--------------------------------------------------------------------------
| Handles WhatsApp notifications for admin-sent messages in the background.
| This prevents the admin dashboard from lagging when sending messages.
*/

namespace App\Jobs;

use App\Models\Message;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAdminMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function handle(WhatsappService $whatsappService)
    {
        try {
            $conversation = $this->message->conversation;
            if (!$conversation || !$conversation->customer) return;

            $messageType = $this->message->message_type;
            $content = $this->message->content;
            $to = $conversation->customer->contact;

            if ($messageType === 'text') {
                $whatsappService->sendMessage($to, "👩‍💼 *Admin:* " . $content);
            } elseif (in_array($messageType, ['image', 'file'])) {
                $whatsappService->sendMedia($to, $content, "👩‍💼 *Admin:* [Kirim Media]", $messageType);
            }

            Log::info("ProcessAdminMessage Job Success for message ID: " . $this->message->id);
        } catch (\Exception $e) {
            Log::error("ProcessAdminMessage Job Failed: " . $e->getMessage());
        }
    }
}
