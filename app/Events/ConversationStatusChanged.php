<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Conversation $conversation, public string $changedBy = 'system')
    {
        //
    }

    /**
     * Broadcast ke:
     * 1. Private channel user (agar user tahu statusnya berubah)
     * 2. Private channel admin dashboard (agar semua admin lihat antrian berubah)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
            new PrivateChannel('admin.dashboard'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'status'          => $this->conversation->status,
            'bot_phase'       => $this->conversation->bot_phase,
            'admin_id'        => $this->conversation->admin_id,
            'queue_position'  => $this->conversation->queue_position,
            'changed_by'      => $this->changed_by ?? 'system',
            'customer'        => [
                'id'        => $this->conversation->customer->id,
                'is_online' => $this->conversation->customer->is_online,
            ]
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.status.changed';
    }
}
