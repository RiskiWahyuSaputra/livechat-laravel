<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        //
    }

    /**
     * Channel yang menerima event ini.
     * Private channel per conversation → hanya peserta yang bisa dengar.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Data yang dikirim ke frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id'       => $this->message->sender_id,
            'sender_type'     => $this->message->sender_type,
            'message_type'    => $this->message->message_type,
            'content'         => $this->message->content,
            'is_whisper'      => $this->message->isWhisper(),
            'created_at'      => $this->message->created_at->toISOString(),
        ];
    }

    /**
     * Nama event di frontend (JavaScript akan listen event ini).
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
