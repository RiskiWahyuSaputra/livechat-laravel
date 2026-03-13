<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappSession extends Model
{
    protected $fillable = [
        'chat_id',
        'user_name',
        'current_flow_id',
        'current_node_id',
        'flow_context',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'flow_context'     => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function currentFlow()
    {
        return $this->belongsTo(ConversationFlow::class, 'current_flow_id');
    }

    public function currentNode()
    {
        return $this->belongsTo(FlowNode::class, 'current_node_id');
    }

    public static function findOrCreateForChat(string $chatId, string $userName = ''): self
    {
        return static::firstOrCreate(
            ['chat_id' => $chatId],
            ['user_name' => $userName, 'flow_context' => []]
        );
    }
}
