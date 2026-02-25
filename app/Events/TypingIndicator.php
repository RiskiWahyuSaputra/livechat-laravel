<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $conversationId,
        public int    $senderId,
        public string $senderType,  // 'user' atau 'admin'
        public string $senderRole,  // 'user', 'super_admin', or 'agent'
        public string $senderName,
        public bool   $isTyping
    ) {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id'       => $this->senderId,
            'sender_type'     => $this->senderType,
            'sender_role'     => $this->senderRole,
            'sender_name'     => $this->senderName,
            'is_typing'       => $this->isTyping,
        ];
    }

    public function broadcastAs(): string
    {
        return 'typing';
    }
}
