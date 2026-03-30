<?php

namespace App\Events;

use App\Models\AdminMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AdminMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin_conversation.' . $this->message->admin_conversation_id),
            new PrivateChannel('admin.dashboard'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'admin_conversation_id' => $this->message->admin_conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender' => [
                'id' => $this->message->sender->id,
                'username' => $this->message->sender->username,
            ],
            'message_type' => $this->message->message_type,
            'content' => $this->message->content,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin_message.sent';
    }
}
