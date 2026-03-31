<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalMessage extends Model
{
    protected $fillable = [
        'internal_conversation_id',
        'sender_id',
        'content',
        'message_type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(InternalConversation::class, 'internal_conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(Admin::class, 'sender_id');
    }
}
