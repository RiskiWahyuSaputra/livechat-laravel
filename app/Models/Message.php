<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
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

    // Relasi ke conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Ambil pengirim secara dinamis (User atau Admin)
    public function sender()
    {
        if ($this->sender_type === 'user') {
            return User::find($this->sender_id);
        } elseif ($this->sender_type === 'admin') {
            return Admin::find($this->sender_id);
        }
        return null; // system message
    }

    // Cek apakah ini pesan whisper (internal admin)
    public function isWhisper(): bool
    {
        return $this->message_type === 'whisper';
    }

    // Cek apakah ini pesan sistem otomatis
    public function isSystem(): bool
    {
        return $this->sender_type === 'system';
    }
}
