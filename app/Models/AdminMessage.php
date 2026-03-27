<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMessage extends Model
{
    protected $fillable = [
        'admin_conversation_id',
        'sender_id',
        'message_type',
        'content',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(AdminConversation::class, 'admin_conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }
}
