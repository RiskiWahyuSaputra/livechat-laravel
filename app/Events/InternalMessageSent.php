<?php

namespace App\Events;

use App\Models\InternalMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(InternalMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        return [
            new PrivateChannel('internal-chat.' . $conversation->user_one_id),
            new PrivateChannel('internal-chat.' . $conversation->user_two_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'sender_id' => $this->message->sender_id,
            'conversation_id' => $this->message->internal_conversation_id,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'internal.message.sent';
    }
}
